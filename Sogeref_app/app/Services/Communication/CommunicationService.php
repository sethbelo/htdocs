<?php

namespace App\Services\Communication;

use App\Models\AuditLog;
use App\Models\CommConversation;
use App\Models\CommMessage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\Communication\Drivers\ChannelDriver;
use App\Services\Communication\Drivers\EmailDriver;
use App\Services\Communication\Drivers\SimulationDriver;
use App\Services\Communication\Drivers\SmsAfricasTalkingDriver;
use App\Services\Communication\Drivers\SmsHttpDriver;
use App\Services\Communication\Drivers\SmsInfobipDriver;
use App\Services\Communication\Drivers\SmsTwilioDriver;
use App\Services\Communication\Drivers\WhatsAppMetaDriver;
use App\Services\Communication\Drivers\WhatsAppTwilioDriver;
use App\Support\Tenant;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoi et réception multicanal (WhatsApp, SMS, e-mail).
 *
 * Chaque envoi crée un CommMessage (statut sent | failed), met à jour la
 * conversation et le journal d'audit. Les pilotes n'émettent jamais
 * d'exception : une erreur fournisseur devient un message « failed » + error.
 * Seule une saisie invalide (contact sans numéro / e-mail, message vide)
 * lève une DomainException, affichée en notification par les écrans.
 *
 * Société : celle de l'utilisateur connecté, ou forCompany($company) pour
 * les tâches de fond et webhooks.
 */
class CommunicationService
{
    public const CHANNELS = ['whatsapp', 'sms', 'email'];

    public const PLACEHOLDERS = [
        '{prenom}' => 'Prénom du contact', '{nom}' => 'Nom du contact', '{nom_complet}' => 'Nom complet / raison sociale',
        '{societe}' => 'Société du contact', '{telephone}' => 'Téléphone', '{email}' => 'E-mail', '{entreprise}' => 'Notre société',
    ];

    private ?Company $company = null;

    public function forCompany(Company $company): static
    {
        $clone = clone $this;
        $clone->company = $company;

        return $clone;
    }

    public function company(): Company
    {
        return $this->company ?? Tenant::company();
    }

    public function settings(): IntegrationSettings
    {
        return IntegrationSettings::for($this->company());
    }

    public function pool(): WhatsAppNumberPool
    {
        return WhatsAppNumberPool::for($this->company());
    }

    // ── Envoi ───────────────────────────────────────────────────────────────

    public function sendWhatsApp(Contact|string $to, string $body, ?User $by = null): CommMessage
    {
        return $this->sendTo('whatsapp', $to, $body, null, $by);
    }

    public function sendSms(Contact|string $to, string $body, ?User $by = null): CommMessage
    {
        return $this->sendTo('sms', $to, $body, null, $by);
    }

    public function sendEmail(Contact|string $to, string $subject, string $body, ?User $by = null): CommMessage
    {
        return $this->sendTo('email', $to, $body, $subject, $by);
    }

    /** Envoi générique (utilisé aussi par les campagnes). */
    public function sendTo(string $channel, Contact|string $to, string $body, ?string $subject = null, ?User $by = null, ?int $campaignId = null): CommMessage
    {
        $contact = $to instanceof Contact ? $to : null;
        $address = $contact ? $this->addressFor($contact, $channel) : (string) $to;
        if (blank($address)) {
            throw new DomainException(match ($channel) {
                'email' => 'Ce contact n’a pas d’adresse e-mail.',
                default => 'Ce contact n’a pas de numéro de téléphone.',
            });
        }
        $conversation = $this->conversationFor($channel, $address, $contact);
        if ($subject !== null && $channel === 'email' && blank($conversation->subject)) {
            $conversation->update(['subject' => $subject]);
        }

        return $this->deliver($conversation, $body, $subject, $by, $campaignId);
    }

    /** Réponse dans une conversation existante (même numéro expéditeur). */
    public function reply(CommConversation $conversation, string $body, ?string $subject = null, ?User $by = null): CommMessage
    {
        if ($conversation->channel === 'email' && $subject === null) {
            $subject = $conversation->subject ? (Str::startsWith($conversation->subject, 'RE:') ? $conversation->subject : 'RE: '.$conversation->subject) : null;
        }
        if ($conversation->status !== 'open') {
            $conversation->update(['status' => 'open']);
        }
        $conversation->markRead();

        return $this->deliver($conversation, $body, $subject, $by);
    }

    /** Conversation ouverte avec cette adresse sur ce canal, créée au besoin. */
    public function conversationFor(string $channel, string $address, ?Contact $contact = null): CommConversation
    {
        $this->assertChannel($channel);
        $address = $this->normalizeAddress($channel, $address);
        $cid = $this->company()->id;

        $conversation = CommConversation::query()->where('company_id', $cid)->channel($channel)->where('address', $address)
            ->open()->latest('last_message_at')->latest('id')->first();
        if ($conversation) {
            if ($contact && ! $conversation->contact_id) {
                $conversation->update(['contact_id' => $contact->id]);
            }

            return $conversation;
        }

        return CommConversation::create([
            'company_id' => $cid,
            'contact_id' => $contact?->id ?? $this->findContact($channel, $address)?->id,
            'channel' => $channel,
            'address' => $address,
            'sender' => $this->senderFor($channel, $address),
            'status' => 'open',
        ]);
    }

    /** Identité d'envoi : numéro WhatsApp (pool), sender id SMS, adresse e-mail. */
    public function senderFor(string $channel, string $address): ?string
    {
        $settings = $this->settings();

        return match ($channel) {
            'whatsapp' => $this->pool()->pick($address)['phone'] ?: null,
            'sms' => $settings->get('sms')['sender_id'] ?: null,
            'email' => $settings->get('email')['from_address'] ?: ($this->company()->email ?: config('mail.from.address')),
        };
    }

    /** Envoie un message dans une conversation et le journalise. */
    public function deliver(CommConversation $conversation, string $body, ?string $subject = null, ?User $by = null, ?int $campaignId = null): CommMessage
    {
        $body = trim($body);
        if ($body === '') {
            throw new DomainException('Le message est vide.');
        }
        $channel = $conversation->channel;
        $settings = $this->settings();
        $provider = $settings->provider($channel);
        $by ??= auth()->user();

        // Le numéro WhatsApp d'origine peut avoir été désactivé : on en réattribue un.
        if ($channel === 'whatsapp' && ($conversation->sender === null || ! collect($this->pool()->active())->contains('phone', $conversation->sender)) && $this->pool()->active()) {
            $conversation->update(['sender' => $this->pool()->pick($conversation->address)['phone']]);
        }

        $message = CommMessage::create([
            'company_id' => $conversation->company_id,
            'conversation_id' => $conversation->id,
            'campaign_id' => $campaignId,
            'direction' => 'out',
            'channel' => $channel,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
            'provider' => $provider,
            'sent_by' => $by?->id,
        ]);

        $result = $this->transmit($channel, $provider, [
            'to' => $conversation->address,
            'from' => $conversation->sender,
            'body' => $body,
            'subject' => $subject,
            'html' => $channel === 'email' ? $this->emailHtml($body) : null,
            'text' => $channel === 'email' ? $this->emailText($body) : null,
            'phone_number_id' => $channel === 'whatsapp' ? $this->pool()->find($conversation->sender)['phone_number_id'] ?? null : null,
        ]);

        $message->update([
            'status' => $result->ok ? 'sent' : 'failed',
            'provider' => $result->provider,
            'provider_message_id' => $result->providerId,
            'error' => $result->error,
            'sent_at' => $result->ok ? now() : null,
        ]);
        $conversation->update(['last_message_at' => now()]);
        $this->audit($message, 'sent', $by);

        return $message;
    }

    /** Envoie directement via le pilote, sans journaliser (tests de connexion). */
    public function transmit(string $channel, string $provider, array $payload): SendResult
    {
        try {
            if (in_array($channel, ['whatsapp', 'sms'], true) && $provider !== 'simulation') {
                $payload['status_callback'] = $this->statusCallback($channel, $provider);
            }

            return $this->driver($channel, $provider)->send($payload);
        } catch (Throwable $e) {
            return SendResult::failed($provider, $e->getMessage());
        }
    }

    public function driver(string $channel, string $provider): ChannelDriver
    {
        if ($provider === 'simulation') {
            return new SimulationDriver($channel);
        }
        $config = $this->settings()->get($channel);
        if ($channel !== 'email' && ! $this->settings()->configured($channel)) {
            return new class($channel) implements ChannelDriver
            {
                public function __construct(private string $channel) {}

                public function send(array $message): SendResult
                {
                    return SendResult::failed('none', 'Canal '.$this->channel.' non configuré (Master système › Intégrations & API).');
                }
            };
        }

        return match ("{$channel}.{$provider}") {
            'email.smtp', 'email.log' => new EmailDriver($config),
            'whatsapp.meta_cloud' => new WhatsAppMetaDriver($config),
            'whatsapp.twilio' => new WhatsAppTwilioDriver($config),
            'sms.twilio' => new SmsTwilioDriver($config),
            'sms.africastalking' => new SmsAfricasTalkingDriver($config),
            'sms.infobip' => new SmsInfobipDriver($config),
            'sms.http' => new SmsHttpDriver($config),
            default => new SimulationDriver($channel),
        };
    }

    /**
     * Test de connexion d'un canal : envoie un court message à $address
     * (pour WhatsApp, depuis le numéro $numberIndex si fourni). Ne crée pas
     * de conversation. Retourne [ok, message lisible].
     */
    public function testChannel(string $channel, string $address, ?int $numberIndex = null): array
    {
        $this->assertChannel($channel);
        $settings = $this->settings();
        $provider = $settings->provider($channel);
        $address = $this->normalizeAddress($channel, $address);
        if ($channel === 'email' ? ! filter_var($address, FILTER_VALIDATE_EMAIL) : strlen($address) < 8) {
            return [false, $channel === 'email' ? 'Adresse e-mail invalide.' : 'Numéro invalide.'];
        }
        $number = $channel === 'whatsapp' ? ($numberIndex !== null ? $this->pool()->all()[$numberIndex] ?? null : $this->pool()->pick($address)) : null;
        $company = $this->company()->name;
        $result = $this->transmit($channel, $provider, [
            'to' => $address,
            'from' => match ($channel) {
                'whatsapp' => $number['phone'] ?? null,
                'sms' => $settings->get('sms')['sender_id'] ?: null,
                'email' => $this->senderFor('email', $address),
            },
            'phone_number_id' => $number['phone_number_id'] ?? null,
            'subject' => "Test de connexion — {$company}",
            'body' => "Message de test envoyé depuis l’ERP {$company}".($number ? " (ligne « {$number['label']} »)" : '').'.',
        ]);

        $label = IntegrationSettings::PROVIDERS[$channel][$result->provider] ?? ($result->provider === 'simulation' ? 'Simulation' : $result->provider);

        return $result->ok
            ? [true, $result->provider === 'simulation' ? 'Simulation : message journalisé, rien n’a été envoyé.' : "Message de test envoyé via {$label}."]
            : [false, "Échec ({$label}) : {$result->error}"];
    }

    // ── Réception ───────────────────────────────────────────────────────────

    /** Message entrant : rattache le contact, incrémente les non-lus. */
    public function receive(string $channel, string $from, string $body, ?string $providerId, ?string $toNumber): CommMessage
    {
        $this->assertChannel($channel);
        if ($providerId && ($existing = CommMessage::query()->where('company_id', $this->company()->id)->where('provider_message_id', $providerId)->where('direction', 'in')->first())) {
            return $existing; // webhook rejoué
        }
        $address = $this->normalizeAddress($channel, $from);
        $conversation = $this->conversationFor($channel, $address);
        if ($channel === 'whatsapp' && $toNumber && ($number = $this->pool()->find($toNumber)) && $number['phone'] !== '' && ! $conversation->messages()->exists()) {
            $conversation->sender = $number['phone'];
        }
        $message = CommMessage::create([
            'company_id' => $conversation->company_id,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'channel' => $channel,
            'body' => $body,
            'status' => 'received',
            'provider' => $channel === 'email' ? 'smtp' : $this->settings()->provider($channel),
            'provider_message_id' => $providerId,
            'sent_at' => now(),
        ]);
        $conversation->forceFill(['last_message_at' => now(), 'unread_count' => $conversation->unread_count + 1, 'status' => 'open'])->save();
        $this->audit($message, 'received', null);

        return $message;
    }

    /** Mise à jour de statut (accusés de remise / lecture des fournisseurs). */
    public function updateStatus(string $providerId, string $status, ?string $error = null): ?CommMessage
    {
        $message = CommMessage::query()->where('company_id', $this->company()->id)->where('provider_message_id', $providerId)->where('direction', 'out')->first();
        if (! $message || ! array_key_exists($status, CommMessage::STATUSES)) {
            return $message;
        }
        $rank = ['queued' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];
        if ($status === 'failed' || ($rank[$status] ?? 0) > ($rank[$message->status] ?? 0)) {
            $message->update(['status' => $status, 'error' => $error ?? $message->error]);
        }

        return $message;
    }

    // ── Modèles & adresses ──────────────────────────────────────────────────

    /** Remplace {prenom} {nom} {nom_complet} {societe} {telephone} {email} {entreprise}. */
    public function renderTemplate(string $text, ?Contact $contact): string
    {
        $values = [
            '{prenom}' => $contact ? ($contact->first_name ?: $contact->display_name) : '',
            '{nom}' => $contact ? ($contact->last_name ?: ($contact->company_name ?: $contact->display_name)) : '',
            '{nom_complet}' => $contact?->display_name ?? '',
            '{societe}' => $contact?->company_name ?? '',
            '{telephone}' => $contact?->phone ?? '',
            '{email}' => $contact?->email ?? '',
            '{entreprise}' => $this->company()->name,
        ];

        return strtr($text, $values);
    }

    /** Adresse d'un contact pour un canal (whatsapp → whatsapp ?? mobile ?? phone, sms → mobile ?? phone, email → email). */
    public function addressFor(Contact $contact, string $channel): ?string
    {
        $mobile = $contact->getAttribute('mobile');
        $value = match ($channel) {
            'whatsapp' => $contact->getAttribute('whatsapp') ?: ($mobile ?: $contact->phone),
            'sms' => $mobile ?: $contact->phone,
            'email' => $contact->email,
            default => null,
        };

        return blank($value) ? null : $this->normalizeAddress($channel, (string) $value);
    }

    public function normalizeAddress(string $channel, string $address): string
    {
        return $channel === 'email' ? Str::lower(trim($address)) : $this->normalizePhone($address);
    }

    /** Format E.164 ; indicatif RDC (+243) par défaut. */
    public function normalizePhone(string $phone): string
    {
        return self::normalizePhoneNumber($phone);
    }

    public static function normalizePhoneNumber(string $phone): string
    {
        $phone = trim(preg_replace('/^(whatsapp|tel|sms):/i', '', trim($phone)));
        $plus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if ($plus) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }
        if (str_starts_with($digits, '243') && strlen($digits) >= 12) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+243'.substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+243'.$digits;
        }

        return '+'.$digits;
    }

    /** Contact de la société correspondant à un numéro ou à une adresse e-mail. */
    public function findContact(string $channel, string $address): ?Contact
    {
        $query = Contact::query()->where('company_id', $this->company()->id);
        if ($channel === 'email') {
            return $query->whereRaw('lower(email) = ?', [Str::lower($address)])->first();
        }
        $e164 = $this->normalizePhone($address);
        $candidates = array_values(array_unique([$e164, ltrim($e164, '+'), '0'.substr($e164, -9)]));
        $columns = array_values(array_filter(['phone', 'whatsapp', 'mobile'], fn ($c) => $this->hasContactColumn($c)));

        return $query->where(function ($q) use ($columns, $candidates) {
            foreach ($columns as $column) {
                $q->orWhereIn(DB::raw("replace(replace(replace(replace({$column}, ' ', ''), '-', ''), '.', ''), '/', '')"), $candidates);
            }
        })->first();
    }

    // ── Interne ─────────────────────────────────────────────────────────────

    private function hasContactColumn(string $column): bool
    {
        static $columns = null;
        $columns ??= Schema::getColumnListing('contacts');

        return in_array($column, $columns, true);
    }

    private function statusCallback(string $channel, string $provider): ?string
    {
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            return null; // les fournisseurs n'appellent que des URL publiques en HTTPS
        }
        try {
            return match (true) {
                $channel === 'whatsapp' && $provider === 'twilio' => route('webhooks.whatsapp.twilio'),
                $channel === 'sms' && $provider === 'twilio' => route('webhooks.sms', ['provider' => 'twilio']),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function emailHtml(string $body): string
    {
        $content = $body !== strip_tags($body) ? $body : nl2br(e($body));
        $company = e($this->company()->name);

        return '<!doctype html><html lang="fr"><body style="margin:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937">'
            .'<div style="max-width:620px;margin:24px auto;background:#fff;border-radius:10px;padding:28px;line-height:1.6;font-size:14px">'.$content.'</div>'
            .'<p style="text-align:center;font-size:11px;color:#9ca3af">'.$company.'</p></body></html>';
    }

    private function emailText(string $body): string
    {
        return trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $body)), ENT_QUOTES, 'UTF-8'));
    }

    private function audit(CommMessage $message, string $event, ?User $by): void
    {
        try {
            AuditLog::create([
                'company_id' => $message->company_id, 'user_id' => $by?->id, 'event' => $event,
                'auditable_type' => $message->getMorphClass(), 'auditable_id' => $message->id, 'old_values' => [],
                'new_values' => ['channel' => $message->channel, 'to' => $message->conversation?->address, 'status' => $message->status, 'provider' => $message->provider, 'campaign_id' => $message->campaign_id],
                'ip_address' => request()?->ip(), 'user_agent' => Str::limit((string) request()?->userAgent(), 250, ''),
            ]);
        } catch (Throwable) {
            // le journal d'audit ne doit jamais bloquer un envoi
        }
    }

    private function assertChannel(string $channel): void
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw new DomainException("Canal inconnu : {$channel}");
        }
    }
}
