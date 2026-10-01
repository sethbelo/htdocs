<?php

namespace App\Services\Communication;

use App\Jobs\SendCampaignJob;
use App\Models\CommCampaign;
use App\Models\CommCampaignRecipient;
use App\Models\CommMessage;
use App\Models\Contact;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Campagnes de masse (WhatsApp, SMS, e-mail) : ciblage, aperçu, test,
 * lancement en tâche de fond et statistiques.
 *
 * audience = {types[], segments[], cities[], contact_ids[], only_opted_in}
 */
class CampaignService
{
    public function __construct(private CommunicationService $communication) {}

    /**
     * Destinataires résolus : un par adresse (dédoublonnés), avec le canal de la campagne.
     *
     * @return Collection<int, array{contact: Contact, address: string}>
     */
    public function audience(CommCampaign $campaign): Collection
    {
        $audience = (array) $campaign->audience;
        $optIn = (bool) ($audience['only_opted_in'] ?? true) && Schema::hasColumn('contacts', 'marketing_opt_in');
        $comm = $this->communication->forCompany($campaign->company);

        return Contact::query()->where('company_id', $campaign->company_id)->where('is_active', true)
            ->when(! empty($audience['contact_ids']), fn ($q) => $q->whereIn('id', (array) $audience['contact_ids']))
            ->when(! empty($audience['types']), fn ($q) => $q->whereIn('type', (array) $audience['types']))
            ->when(! empty($audience['segments']), fn ($q) => $q->whereIn('segment', (array) $audience['segments']))
            ->when(! empty($audience['cities']), fn ($q) => $q->whereIn('city', (array) $audience['cities']))
            ->when($optIn, fn ($q) => $q->where('marketing_opt_in', true))
            ->orderBy('id')->get()
            ->map(fn (Contact $contact) => ['contact' => $contact, 'address' => $comm->addressFor($contact, $campaign->channel)])
            ->filter(fn (array $row) => filled($row['address']))
            ->unique('address')
            ->values();
    }

    /** Aperçu des premiers messages, variables remplacées. */
    public function preview(CommCampaign $campaign, int $limit = 5): Collection
    {
        $comm = $this->communication->forCompany($campaign->company);

        return $this->audience($campaign)->take($limit)->map(fn (array $row) => [
            'contact' => $row['contact'],
            'address' => $row['address'],
            'subject' => $campaign->subject ? $comm->renderTemplate($campaign->subject, $row['contact']) : null,
            'body' => $comm->renderTemplate($campaign->body, $row['contact']),
        ]);
    }

    /** Envoi d'essai à une adresse saisie (variables remplies avec des valeurs d'exemple). */
    public function test(CommCampaign $campaign, string $address): CommMessage
    {
        $this->assertReady($campaign);
        $comm = $this->communication->forCompany($campaign->company);
        $sample = new Contact(['first_name' => 'Prénom', 'last_name' => 'Nom', 'company_name' => 'Société exemple']);

        return $comm->sendTo($campaign->channel, $address, '[TEST] '.$comm->renderTemplate($campaign->body, $sample),
            $campaign->subject ? '[TEST] '.$comm->renderTemplate($campaign->subject, $sample) : null, auth()->user());
    }

    /** Programme (si scheduled_at futur) ou lance immédiatement la campagne. */
    public function launch(CommCampaign $campaign): void
    {
        $this->assertReady($campaign);
        if (! $campaign->isEditable()) {
            throw new DomainException('Cette campagne a déjà été lancée.');
        }
        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $campaign->update(['status' => 'scheduled']);

            return;
        }

        $rows = $this->audience($campaign);
        if ($rows->isEmpty()) {
            throw new DomainException('Aucun destinataire ne correspond au ciblage.');
        }

        $campaign->recipients()->delete();
        foreach ($rows->chunk(500) as $chunk) {
            CommCampaignRecipient::insert($chunk->map(fn (array $row) => [
                'campaign_id' => $campaign->id, 'contact_id' => $row['contact']->id, 'address' => $row['address'],
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ])->all());
        }
        $campaign->update(['status' => 'sending', 'started_at' => now(), 'stats' => ['total' => $rows->count(), 'sent' => 0, 'failed' => 0, 'delivered' => 0]]);

        // Pas de worker de file requis : en console (tests, planificateur) l'envoi est immédiat,
        // en web il s'exécute juste après la réponse envoyée au navigateur.
        app()->runningInConsole() ? SendCampaignJob::dispatchSync($campaign->id) : SendCampaignJob::dispatchAfterResponse($campaign->id);
    }

    /** Envoie les destinataires en attente (appelé par la tâche de fond). */
    public function process(CommCampaign $campaign, int $pauseMs = 0): void
    {
        if ($campaign->status !== 'sending') {
            return;
        }
        $comm = $this->communication->forCompany($campaign->company);
        $creator = $campaign->creator;

        $campaign->recipients()->with('contact')->where('status', 'pending')->chunkById(100, function ($recipients) use ($campaign, $comm, $creator, $pauseMs) {
            foreach ($recipients as $recipient) {
                if ($campaign->fresh()->status !== 'sending') {
                    return false; // annulée en cours de route
                }
                try {
                    $contact = $recipient->contact;
                    $message = $comm->sendTo($campaign->channel, $recipient->address, $comm->renderTemplate($campaign->body, $contact),
                        $campaign->subject ? $comm->renderTemplate($campaign->subject, $contact) : null, $creator, $campaign->id);
                    $recipient->update(['status' => $message->status === 'failed' ? 'failed' : 'sent', 'message_id' => $message->id, 'error' => $message->error]);
                } catch (Throwable $exception) {
                    $recipient->update(['status' => 'failed', 'error' => $exception->getMessage()]);
                }
                if ($pauseMs > 0) {
                    usleep($pauseMs * 1000);
                }
            }
        });

        $this->refreshStats($campaign);
        if (! $campaign->recipients()->where('status', 'pending')->exists() && $campaign->fresh()->status === 'sending') {
            $campaign->update(['status' => 'sent', 'finished_at' => now()]);
        }
    }

    public function refreshStats(CommCampaign $campaign): void
    {
        $counts = $campaign->recipients()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $delivered = $campaign->messages()->whereIn('status', ['delivered', 'read'])->count();
        $campaign->update(['stats' => [
            'total' => (int) $counts->sum(), 'sent' => (int) ($counts['sent'] ?? 0), 'failed' => (int) ($counts['failed'] ?? 0),
            'delivered' => $delivered, 'pending' => (int) ($counts['pending'] ?? 0),
        ]]);
    }

    public function cancel(CommCampaign $campaign): void
    {
        if (in_array($campaign->status, ['sent', 'cancelled'], true)) {
            throw new DomainException('Cette campagne ne peut plus être annulée.');
        }
        $campaign->recipients()->where('status', 'pending')->update(['status' => 'skipped']);
        $campaign->update(['status' => 'cancelled', 'finished_at' => now()]);
        $this->refreshStats($campaign);
    }

    /** Lance les campagnes programmées arrivées à échéance (commande planifiée). */
    public function dispatchDue(): int
    {
        $due = CommCampaign::withoutGlobalScopes()->where('status', 'scheduled')->where('scheduled_at', '<=', now())->get();
        foreach ($due as $campaign) {
            try {
                $campaign->update(['status' => 'draft', 'scheduled_at' => null]);
                $this->launch($campaign);
            } catch (Throwable $exception) {
                $campaign->update(['status' => 'cancelled', 'stats' => ['error' => $exception->getMessage()] + (array) $campaign->stats]);
            }
        }

        return $due->count();
    }

    private function assertReady(CommCampaign $campaign): void
    {
        if (! in_array($campaign->channel, CommunicationService::CHANNELS, true)) {
            throw new DomainException('Choisissez un canal.');
        }
        if (blank($campaign->body)) {
            throw new DomainException('Le message de la campagne est vide.');
        }
        if ($campaign->channel === 'email' && blank($campaign->subject)) {
            throw new DomainException('Un objet est requis pour une campagne e-mail.');
        }
    }
}
