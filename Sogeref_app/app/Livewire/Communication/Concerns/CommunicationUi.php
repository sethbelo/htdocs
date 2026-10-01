<?php

namespace App\Livewire\Communication\Concerns;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\CommTemplate;
use App\Models\Contact;
use App\Services\Communication\CommunicationService;
use Illuminate\Support\Collection;

/**
 * Outils communs aux écrans Communication & marketing : canaux, statuts,
 * droits d'envoi, modèles de messages et recherche de contacts.
 */
trait CommunicationUi
{
    use InteractsWithUi;

    /** Variables disponibles dans les modèles et campagnes. */
    public const PLACEHOLDERS = [
        '{prenom}' => 'Prénom du contact',
        '{nom}' => 'Nom de famille',
        '{nom_complet}' => 'Nom complet / raison sociale',
        '{societe}' => 'Société du contact',
        '{telephone}' => 'Téléphone',
        '{email}' => 'Adresse e-mail',
        '{entreprise}' => 'Notre entreprise (SOGEREF)',
    ];

    /** @return array<string, array{label: string, icon: string, tone: string, route: string}> */
    public static function channels(): array
    {
        return [
            'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'chat-bubble-oval-left-ellipsis', 'tone' => 'success', 'route' => 'communication.whatsapp'],
            'sms' => ['label' => 'SMS', 'icon' => 'device-phone-mobile', 'tone' => 'info', 'route' => 'communication.sms'],
            'email' => ['label' => 'E-mail', 'icon' => 'envelope', 'tone' => 'brand', 'route' => 'communication.emails'],
        ];
    }

    /** Libellé, ton et icône d'un statut (message, campagne, destinataire). */
    public static function statusMeta(?string $status): array
    {
        return [
            'queued' => ['En file', 'neutral', 'clock'],
            'pending' => ['En attente', 'neutral', 'clock'],
            'sent' => ['Envoyé', 'info', 'check'],
            'delivered' => ['Distribué', 'accent', 'check-circle'],
            'read' => ['Lu', 'success', 'eye'],
            'failed' => ['Échec', 'danger', 'exclamation-circle'],
            'received' => ['Reçu', 'brand', 'arrow-down-left'],
            'skipped' => ['Ignoré', 'warning', 'minus-circle'],
            'draft' => ['Brouillon', 'neutral', 'pencil-square'],
            'scheduled' => ['Programmée', 'warning', 'calendar'],
            'sending' => ['En cours d’envoi', 'info', 'paper-airplane'],
            'cancelled' => ['Annulée', 'danger', 'x-circle'],
            'open' => ['Ouverte', 'success', 'inbox'],
            'closed' => ['Clôturée', 'neutral', 'archive-box'],
        ][$status] ?? [ucfirst((string) $status), 'neutral', 'information-circle'];
    }

    protected function canSend(): bool
    {
        $user = auth()->user();

        return $user->isMaster() || $user->canManage('communication') || $user->can('communication.send');
    }

    protected function canCampaign(): bool
    {
        $user = auth()->user();

        return $user->isMaster() || $user->can('communication.campaign');
    }

    protected function authorizeSend(): void
    {
        abort_unless($this->canSend(), 403, 'Vous n’êtes pas autorisé à envoyer des messages.');
    }

    protected function comm(): CommunicationService
    {
        return app(CommunicationService::class);
    }

    /** Modèles disponibles pour un canal. */
    protected function templatesFor(string $channel): Collection
    {
        return CommTemplate::query()->where('company_id', company_id())->where('channel', $channel)->orderBy('name')->get();
    }

    protected function findTemplate(int $id): CommTemplate
    {
        return CommTemplate::query()->where('company_id', company_id())->findOrFail($id);
    }

    protected function findContact(?int $id): ?Contact
    {
        return $id ? Contact::query()->where('company_id', company_id())->find($id) : null;
    }

    /** Recherche de contacts (nom, raison sociale, téléphone, e-mail) pour les sélecteurs. */
    protected function searchContacts(string $term, ?string $channel = null, int $limit = 8): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        return Contact::query()->where('company_id', company_id())->where('is_active', true)
            ->when($channel === 'email', fn ($q) => $q->whereNotNull('email')->where('email', '!=', ''))
            ->when(in_array($channel, ['whatsapp', 'sms'], true), fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', ''))
            ->where(fn ($q) => $q->where('company_name', 'like', "%{$term}%")->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%"))
            ->orderBy('company_name')->orderBy('last_name')->limit($limit)->get();
    }

    /** Exécute un envoi : les erreurs fournisseur deviennent une notification. */
    protected function attemptSend(callable $callback, string $success): mixed
    {
        try {
            $result = $callback();
            if (is_object($result) && ($result->status ?? null) === 'failed') {
                $this->toast('Échec de l’envoi : '.($result->error ?: 'erreur du fournisseur.'), 'error');

                return $result;
            }
            $this->toast($success);

            return $result ?? true;
        } catch (\DomainException|\InvalidArgumentException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return false;
        }
    }
}
