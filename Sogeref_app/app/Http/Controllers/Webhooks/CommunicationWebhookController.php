<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Communication\CommunicationService;
use App\Services\Communication\IntegrationSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Webhooks publics des fournisseurs (messages entrants et accusés de remise).
 * La société est identifiée par le numéro destinataire (l'un des 3 numéros WhatsApp,
 * ou l'expéditeur SMS) ; à défaut, la première société.
 */
class CommunicationWebhookController extends Controller
{
    public function __construct(private CommunicationService $communication) {}

    /** Vérification Meta (hub.challenge). */
    public function metaVerify(Request $request): Response
    {
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        foreach (Company::all() as $company) {
            $expected = (string) (IntegrationSettings::for($company)->get('whatsapp')['verify_token'] ?? '');
            if ($expected !== '' && hash_equals($expected, $token) && $request->query('hub_mode', $request->query('hub.mode')) === 'subscribe') {
                return response((string) $request->query('hub_challenge', $request->query('hub.challenge')), 200);
            }
        }

        return response('Jeton de vérification invalide.', 403);
    }

    /** Messages et statuts WhatsApp Cloud API (Meta). */
    public function meta(Request $request): Response
    {
        $payload = $request->json()->all();
        foreach ((array) data_get($payload, 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $value = (array) data_get($change, 'value', []);
                $to = data_get($value, 'metadata.display_phone_number') ?: data_get($value, 'metadata.phone_number_id');
                $company = $this->companyFor('whatsapp', (string) $to);
                if (! $this->validMetaSignature($request, $company)) {
                    return response('Signature invalide.', 403);
                }
                $comm = $this->communication->forCompany($company);
                foreach ((array) data_get($value, 'messages', []) as $message) {
                    $body = data_get($message, 'text.body') ?? data_get($message, 'button.text') ?? data_get($message, 'interactive.button_reply.title') ?? '['.data_get($message, 'type', 'message').']';
                    $this->safely(fn () => $comm->receive('whatsapp', (string) data_get($message, 'from'), (string) $body, data_get($message, 'id'), (string) $to));
                }
                foreach ((array) data_get($value, 'statuses', []) as $status) {
                    $this->safely(fn () => $comm->updateStatus((string) data_get($status, 'id'), (string) data_get($status, 'status'), data_get($status, 'errors.0.title')));
                }
            }
        }

        return response('OK', 200);
    }

    /** WhatsApp via Twilio (messages entrants et callbacks de statut). */
    public function twilioWhatsApp(Request $request): Response
    {
        $to = (string) $request->input('To');
        $company = $this->companyFor('whatsapp', $to);
        if (! $this->validTwilioSignature($request, (string) (IntegrationSettings::for($company)->get('whatsapp')['auth_token'] ?? ''))) {
            return response('Signature invalide.', 403);
        }
        $this->handleTwilio($request, $this->communication->forCompany($company), 'whatsapp', $to);

        return response('<Response/>', 200, ['Content-Type' => 'text/xml']);
    }

    /** SMS entrants / statuts (Twilio, Africa's Talking, Infobip, passerelle HTTP). */
    public function sms(Request $request, string $provider): Response
    {
        $to = (string) ($request->input('To') ?? $request->input('to') ?? '');
        $company = $this->companyFor('sms', $to);
        $valid = $provider === 'twilio'
            ? $this->validTwilioSignature($request, (string) (IntegrationSettings::for($company)->get('sms')['auth_token'] ?? ''))
            : hash_equals(self::smsSecret(), (string) $request->query('cle'));
        if (! $valid) {
            return response('Signature invalide.', 403);
        }
        $comm = $this->communication->forCompany($company);

        match ($provider) {
            'twilio' => $this->handleTwilio($request, $comm, 'sms', $to),
            'africastalking' => $request->filled('status')
                ? $this->safely(fn () => $comm->updateStatus((string) $request->input('id'), $this->mapStatus((string) $request->input('status'))))
                : $this->safely(fn () => $comm->receive('sms', (string) $request->input('from'), (string) $request->input('text'), $request->input('id'), $to)),
            'infobip' => collect((array) $request->input('results', []))->each(fn ($r) => isset($r['status'])
                ? $this->safely(fn () => $comm->updateStatus((string) ($r['messageId'] ?? ''), $this->mapStatus((string) data_get($r, 'status.groupName'))))
                : $this->safely(fn () => $comm->receive('sms', (string) ($r['from'] ?? ''), (string) ($r['text'] ?? $r['cleanText'] ?? ''), $r['messageId'] ?? null, (string) ($r['to'] ?? '')))),
            default => $request->filled('status')
                ? $this->safely(fn () => $comm->updateStatus((string) $request->input('id'), $this->mapStatus((string) $request->input('status'))))
                : $this->safely(fn () => $comm->receive('sms', (string) $request->input('from'), (string) ($request->input('text') ?? $request->input('message')), $request->input('id'), $to)),
        };

        return response('OK', 200);
    }

    private function handleTwilio(Request $request, CommunicationService $comm, string $channel, string $to): void
    {
        if ($request->filled('MessageStatus') && ! $request->filled('Body')) {
            $this->safely(fn () => $comm->updateStatus((string) $request->input('MessageSid'), $this->mapStatus((string) $request->input('MessageStatus')), $request->input('ErrorMessage')));

            return;
        }
        $this->safely(fn () => $comm->receive($channel, (string) $request->input('From'), (string) $request->input('Body'), $request->input('MessageSid'), $to));
    }

    private function companyFor(string $channel, string $to): Company
    {
        $digits = preg_replace('/\D/', '', $to);
        if ($digits !== '') {
            foreach (Company::all() as $company) {
                $settings = IntegrationSettings::for($company)->get($channel);
                $candidates = $channel === 'whatsapp'
                    ? collect($settings['numbers'] ?? [])->flatMap(fn ($n) => [preg_replace('/\D/', '', (string) ($n['phone'] ?? '')), (string) ($n['phone_number_id'] ?? '')])
                    : collect([preg_replace('/\D/', '', (string) ($settings['sender_id'] ?? ''))]);
                if ($candidates->filter()->contains(fn ($c) => $c === $digits || str_ends_with($digits, $c) || str_ends_with($c, $digits))) {
                    return $company;
                }
            }
        }

        return Company::query()->orderBy('id')->firstOrFail();
    }

    private function validMetaSignature(Request $request, Company $company): bool
    {
        $secret = (string) (IntegrationSettings::for($company)->get('whatsapp')['app_secret'] ?? '');
        if ($secret === '') {
            return true; // pas de secret configuré : vérification impossible (mode test)
        }
        $signature = (string) $request->header('X-Hub-Signature-256');

        return $signature !== '' && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature);
    }

    /** Clé à ajouter (?cle=…) aux URL de webhook SMS hors Twilio. */
    public static function smsSecret(): string
    {
        return substr(hash_hmac('sha256', 'sogeref-sms-webhook', (string) config('app.key')), 0, 32);
    }

    /** Signature X-Twilio-Signature (HMAC-SHA1 de l'URL + paramètres triés). */
    private function validTwilioSignature(Request $request, string $authToken): bool
    {
        if ($authToken === '') {
            return true; // jeton non configuré (simulation / tests)
        }
        $params = $request->post();
        ksort($params);
        $data = $request->fullUrl().collect($params)->map(fn ($v, $k) => $k.$v)->implode('');
        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, (string) $request->header('X-Twilio-Signature'));
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'queued', 'accepted', 'sending', 'pending' => 'queued',
            'sent', 'submitted', 'buffered' => 'sent',
            'delivered', 'success' => 'delivered',
            'read' => 'read',
            'failed', 'undelivered', 'rejected', 'undeliverable', 'expired' => 'failed',
            default => 'sent',
        };
    }

    private function safely(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::warning('Webhook communication ignoré : '.$exception->getMessage());

            return null;
        }
    }
}
