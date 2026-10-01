<?php

namespace App\Services\Communication;

use App\Models\CommConversation;
use App\Models\CommMessage;
use App\Models\Company;
use App\Support\Tenant;

/**
 * Les trois numéros WhatsApp de la société. Un correspondant garde toujours
 * le même numéro (« collant ») ; un nouveau correspondant reçoit un numéro
 * actif tiré au hasard, ce qui répartit la charge entre les trois lignes.
 */
class WhatsAppNumberPool
{
    public function __construct(private ?Company $company = null) {}

    public static function for(Company $company): self
    {
        return new self($company);
    }

    private function company(): Company
    {
        return $this->company ?? Tenant::company();
    }

    /** Les 3 numéros configurés (index, label, phone, phone_number_id, active). */
    public function all(): array
    {
        $numbers = IntegrationSettings::for($this->company())->get('whatsapp')['numbers'];

        return array_map(fn (array $n, int $i) => $n + ['index' => $i], $numbers, array_keys($numbers));
    }

    /** Numéros actifs disposant d'un numéro de téléphone. */
    public function active(): array
    {
        return array_values(array_filter($this->all(), fn ($n) => $n['active'] && $n['phone'] !== ''));
    }

    /** Numéro à utiliser pour écrire à $address (collant, sinon aléatoire parmi les actifs). */
    public function pick(string $address): array
    {
        $address = CommunicationService::normalizePhoneNumber($address);
        $active = $this->active();
        $byPhone = collect($active)->keyBy('phone');

        $previous = CommConversation::query()->where('company_id', $this->company()->id)->channel('whatsapp')
            ->where('address', $address)->whereNotNull('sender')
            ->orderByRaw("case when status = 'open' then 0 else 1 end")->latest('last_message_at')->latest('id')
            ->pluck('sender');
        foreach ($previous as $sender) {
            if ($byPhone->has($sender)) {
                return $byPhone->get($sender);
            }
        }

        if ($active) {
            return $active[array_rand($active)];
        }

        // Aucun numéro actif : premier numéro renseigné (utile en simulation), sinon emplacement vide.
        return collect($this->all())->first(fn ($n) => $n['phone'] !== '') ?? $this->all()[0];
    }

    /** Le numéro correspondant à un téléphone ou à un phone_number_id Meta. */
    public function find(?string $phoneOrId): ?array
    {
        if (blank($phoneOrId)) {
            return null;
        }
        $phone = CommunicationService::normalizePhoneNumber($phoneOrId);

        return collect($this->all())->first(fn ($n) => ($n['phone'] !== '' && $n['phone'] === $phone) || ($n['phone_number_id'] !== '' && $n['phone_number_id'] === $phoneOrId));
    }

    /** Liste des 3 numéros avec statistiques (conversations, messages sur 30 jours). */
    public function numbers(): array
    {
        $cid = $this->company()->id;
        $phones = array_filter(array_column($this->all(), 'phone'));
        $conversations = CommConversation::query()->where('company_id', $cid)->channel('whatsapp')->whereIn('sender', $phones)
            ->selectRaw('sender, count(*) as total, sum(unread_count) as unread')->groupBy('sender')->get()->keyBy('sender');
        $messages = CommMessage::query()->where('comm_messages.company_id', $cid)->where('comm_messages.channel', 'whatsapp')
            ->where('comm_messages.created_at', '>=', now()->subDays(30))
            ->join('comm_conversations', 'comm_conversations.id', '=', 'comm_messages.conversation_id')
            ->whereIn('comm_conversations.sender', $phones)
            ->selectRaw("comm_conversations.sender as sender, count(*) as total, sum(case when comm_messages.direction = 'in' then 1 else 0 end) as inbound")
            ->groupBy('comm_conversations.sender')->get()->keyBy('sender');

        return array_map(fn (array $n) => $n + [
            'conversations' => (int) ($conversations[$n['phone']]->total ?? 0),
            'unread' => (int) ($conversations[$n['phone']]->unread ?? 0),
            'messages_30d' => (int) ($messages[$n['phone']]->total ?? 0),
            'inbound_30d' => (int) ($messages[$n['phone']]->inbound ?? 0),
        ], $this->all());
    }
}
