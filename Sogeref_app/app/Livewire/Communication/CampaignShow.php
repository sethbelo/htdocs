<?php

namespace App\Livewire\Communication;

use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Models\CommCampaign;
use App\Services\Communication\CampaignService;
use DomainException;
use Livewire\Component;
use Livewire\WithPagination;

/** Détail d'une campagne : indicateurs, message envoyé et statut de chaque destinataire. */
class CampaignShow extends Component
{
    use CommunicationUi, WithPagination;

    public CommCampaign $campaign;

    public string $status = 'all';

    public function mount(CommCampaign $campaign): void
    {
        abort_unless($campaign->company_id === company_id(), 404);
        $this->campaign = $campaign;
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function cancel(): void
    {
        abort_unless($this->canCampaign(), 403);
        try {
            app(CampaignService::class)->cancel($this->campaign);
            $this->toast('Campagne annulée.');
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    public function retryFailed(): void
    {
        abort_unless($this->canCampaign(), 403);
        $failed = $this->campaign->recipients()->where('status', 'failed')->update(['status' => 'pending', 'error' => null]);
        if (! $failed) {
            $this->toast('Aucun envoi en échec à relancer.', 'warning');

            return;
        }
        $this->campaign->update(['status' => 'sending', 'finished_at' => null]);
        app(CampaignService::class)->process($this->campaign->fresh());
        $this->toast($failed.' envoi(s) relancé(s).');
    }

    public function render()
    {
        $this->campaign->refresh();
        if ($this->campaign->status === 'sending') {
            app(CampaignService::class)->refreshStats($this->campaign);
        }

        return view('livewire.communication.campaign-show', [
            'recipients' => $this->campaign->recipients()->with('contact')
                ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
                ->orderBy('id')->paginate(25),
            'meta' => self::channels()[$this->campaign->channel] ?? self::channels()['whatsapp'],
            'canCampaign' => $this->canCampaign(),
        ])->title($this->campaign->name);
    }
}
