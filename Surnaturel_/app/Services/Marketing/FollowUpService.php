<?php

namespace App\Services\Marketing;

use App\Enums\InteractionChannel;
use App\Enums\InteractionOutcome;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\ClientInteraction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Suivi marketing des clients : appels, messages WhatsApp, notes et relances planifiées.
 */
class FollowUpService
{
    /**
     * @param  array{channel: string, outcome?: string|null, subject?: string|null, notes?: string|null, duration_minutes?: int|string|null, next_follow_up_at?: string|null, direction?: string|null}  $data
     */
    public function record(Client $client, User $agent, array $data): ClientInteraction
    {
        $channel = InteractionChannel::from($data['channel']);
        $outcome = filled($data['outcome'] ?? null) ? InteractionOutcome::from($data['outcome']) : null;
        $next = filled($data['next_follow_up_at'] ?? null) ? Carbon::parse($data['next_follow_up_at']) : null;

        if ($outcome === InteractionOutcome::Callback && ! $next) {
            throw new BusinessRuleException('Indiquez la date de rappel.');
        }

        if ($next && $next->isPast()) {
            throw new BusinessRuleException('La date de relance doit être dans le futur.');
        }

        return DB::transaction(function () use ($client, $agent, $data, $channel, $outcome, $next): ClientInteraction {
            $interaction = $client->interactions()->create([
                'user_id' => $agent->id,
                'channel' => $channel,
                'direction' => $data['direction'] ?? 'outgoing',
                'outcome' => $outcome,
                'subject' => $data['subject'] ?? null,
                'notes' => $data['notes'] ?? null,
                'duration_seconds' => filled($data['duration_minutes'] ?? null) ? (int) round((float) $data['duration_minutes'] * 60) : null,
                'next_follow_up_at' => $next,
                'occurred_at' => now(),
            ]);

            $client->forceFill([
                'last_contacted_at' => $channel === InteractionChannel::Note ? $client->last_contacted_at : now(),
                'next_follow_up_at' => $next ?? ($outcome === InteractionOutcome::NotInterested || ! $client->next_follow_up_at?->isFuture() ? null : $client->next_follow_up_at),
                'follow_up_owner_id' => $client->follow_up_owner_id ?? $agent->id,
            ])->save();

            return $interaction;
        });
    }

    /**
     * @return list<array{name: string, body: string}>
     */
    public function templates(): array
    {
        return Setting::get('marketing.whatsapp_templates', []);
    }

    public function renderTemplate(string $body, Client $client, User $agent): string
    {
        return strtr($body, [
            '{client}' => $client->displayName(),
            '{agent}' => $agent->name,
            '{entreprise}' => (string) Setting::get('company.name', config('app.name')),
        ]);
    }

    public function whatsappUrl(Client $client, string $message = ''): ?string
    {
        $number = $client->whatsappNumber();

        return $number ? 'https://wa.me/'.$number.($message !== '' ? '?text='.rawurlencode($message) : '') : null;
    }

    public function callUrl(Client $client): ?string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) ($client->phone ?: $client->whatsapp));

        return $phone ? 'tel:'.$phone : null;
    }
}
