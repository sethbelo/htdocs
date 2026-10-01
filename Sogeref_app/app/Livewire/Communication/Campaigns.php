<?php

namespace App\Livewire\Communication;

use App\Enums\ContactType;
use App\Livewire\Communication\Concerns\CommunicationUi;
use App\Models\CommCampaign;
use App\Models\Contact;
use App\Services\Communication\CampaignService;
use App\Support\Tenant;
use DomainException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Campagnes WhatsApp / SMS / e-mail : liste et assistant en 4 étapes
 * (canal, ciblage avec compte en direct, message, envoi / programmation / test).
 */
#[Title('Campagnes')]
class Campaigns extends Component
{
    use CommunicationUi;

    public string $status = 'all';

    public string $search = '';

    public bool $showWizard = false;

    public int $step = 1;

    public ?int $editingId = null;

    public array $form = [];

    public string $contactSearch = '';

    public string $testAddress = '';

    public function mount(): void
    {
        $this->form = $this->blank();
    }

    private function blank(): array
    {
        return [
            'name' => '', 'channel' => 'whatsapp', 'subject' => '', 'body' => '', 'scheduled_at' => '',
            'types' => ['client'], 'segments' => [], 'cities' => [], 'contact_ids' => [], 'only_opted_in' => true,
        ];
    }

    private function services(): CampaignService
    {
        return app(CampaignService::class);
    }

    private function find(int $id): CommCampaign
    {
        return CommCampaign::query()->where('company_id', company_id())->findOrFail($id);
    }

    private function guardCampaign(): void
    {
        abort_unless($this->canCampaign(), 403, 'Seuls les responsables communication peuvent gérer les campagnes.');
    }

    public function create(): void
    {
        $this->guardCampaign();
        $this->resetValidation();
        $this->editingId = null;
        $this->form = $this->blank();
        $this->step = 1;
        $this->testAddress = '';
        $this->showWizard = true;
    }

    public function edit(int $id): void
    {
        $this->guardCampaign();
        $campaign = $this->find($id);
        if (! $campaign->isEditable()) {
            $this->toast('Cette campagne a déjà été lancée.', 'warning');

            return;
        }
        $audience = (array) $campaign->audience;
        $this->form = [
            'name' => $campaign->name, 'channel' => $campaign->channel, 'subject' => (string) $campaign->subject, 'body' => $campaign->body,
            'scheduled_at' => $campaign->scheduled_at?->format('Y-m-d\TH:i') ?? '',
            'types' => $audience['types'] ?? [], 'segments' => $audience['segments'] ?? [], 'cities' => $audience['cities'] ?? [],
            'contact_ids' => array_map('intval', $audience['contact_ids'] ?? []), 'only_opted_in' => (bool) ($audience['only_opted_in'] ?? true),
        ];
        $this->editingId = $campaign->id;
        $this->step = 1;
        $this->resetValidation();
        $this->showWizard = true;
    }

    public function duplicate(int $id): void
    {
        $this->guardCampaign();
        $source = $this->find($id);
        CommCampaign::create([
            'company_id' => company_id(), 'name' => str($source->name)->limit(170)->append(' (copie)')->toString(), 'channel' => $source->channel,
            'subject' => $source->subject, 'body' => $source->body, 'audience' => $source->audience, 'status' => 'draft', 'created_by' => auth()->id(),
        ]);
        $this->toast('Campagne dupliquée en brouillon.');
    }

    public function delete(int $id): void
    {
        $this->guardCampaign();
        $campaign = $this->find($id);
        if (! in_array($campaign->status, ['draft', 'cancelled'], true)) {
            $this->toast('Seuls les brouillons et campagnes annulées peuvent être supprimés.', 'warning');

            return;
        }
        $campaign->delete();
        $this->toast('Campagne supprimée.');
    }

    public function goTo(int $step): void
    {
        if ($step > $this->step) {
            $this->validateStep($this->step);
        }
        $this->step = max(1, min(4, $step));
    }

    public function next(): void
    {
        $this->goTo($this->step + 1);
    }

    private function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate(['form.name' => 'required|string|max:190', 'form.channel' => 'required|in:whatsapp,sms,email'], [], ['form.name' => 'nom de la campagne']),
            2 => $this->audienceCount() > 0 ?: throw ValidationException::withMessages(['form.types' => 'Aucun contact ne correspond à ce ciblage.']),
            3 => $this->validate([
                'form.subject' => ($this->form['channel'] === 'email' ? 'required' : 'nullable').'|string|max:190',
                'form.body' => 'required|string|max:'.($this->form['channel'] === 'sms' ? 1600 : 20000),
            ], [], ['form.subject' => 'objet', 'form.body' => 'message']),
            default => null,
        };
    }

    public function toggleContact(int $id): void
    {
        $ids = collect($this->form['contact_ids']);
        $this->form['contact_ids'] = ($ids->contains($id) ? $ids->reject(fn ($v) => $v === $id) : $ids->push($id))->values()->all();
        $this->contactSearch = '';
    }

    public function useTemplate(int $id, string $target = 'body'): void
    {
        $template = $this->findTemplate($id);
        $this->form['body'] = $template->body;
        if ($template->subject) {
            $this->form['subject'] = $template->subject;
        }
    }

    public function insert(string $placeholder): void
    {
        $this->form['body'] = rtrim($this->form['body']).($this->form['body'] === '' ? '' : ' ').$placeholder;
    }

    /** Enregistre le brouillon et renvoie la campagne. */
    private function persist(): CommCampaign
    {
        foreach ([1, 3] as $step) {
            $this->validateStep($step);
        }
        $data = [
            'name' => trim($this->form['name']), 'channel' => $this->form['channel'],
            'subject' => $this->form['channel'] === 'email' ? trim($this->form['subject']) : null, 'body' => trim($this->form['body']),
            'scheduled_at' => $this->form['scheduled_at'] ?: null,
            'audience' => [
                'types' => array_values($this->form['types']), 'segments' => array_values($this->form['segments']), 'cities' => array_values($this->form['cities']),
                'contact_ids' => array_values(array_map('intval', $this->form['contact_ids'])), 'only_opted_in' => (bool) $this->form['only_opted_in'],
            ],
        ];
        if ($this->editingId) {
            $campaign = $this->find($this->editingId);
            $campaign->update($data + ['status' => 'draft']);

            return $campaign;
        }
        $campaign = CommCampaign::create($data + ['company_id' => company_id(), 'status' => 'draft', 'created_by' => auth()->id()]);
        $this->editingId = $campaign->id;

        return $campaign;
    }

    public function saveDraft(): void
    {
        $this->guardCampaign();
        $this->persist();
        $this->showWizard = false;
        $this->toast('Brouillon enregistré.');
    }

    public function sendTest(): void
    {
        $this->guardCampaign();
        $this->validate(['testAddress' => 'required|string|max:120'], [], ['testAddress' => 'adresse de test']);
        $campaign = $this->persist();
        $this->attemptSend(fn () => $this->services()->test($campaign, $this->testAddress), 'Message de test envoyé à '.$this->testAddress.'.');
    }

    public function launch(): void
    {
        $this->guardCampaign();
        $this->validateStep(2);
        $campaign = $this->persist();
        try {
            $this->services()->launch($campaign);
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return;
        }
        $campaign->refresh();
        $this->showWizard = false;
        $this->toast($campaign->status === 'scheduled'
            ? 'Campagne programmée pour le '.$campaign->scheduled_at->translatedFormat('j F Y à H:i').'.'
            : 'Campagne lancée : '.$campaign->stat('total').' destinataire(s).');
        $this->redirectRoute('communication.campaigns.show', $campaign, navigate: true);
    }

    public function cancel(int $id): void
    {
        $this->guardCampaign();
        try {
            $this->services()->cancel($this->find($id));
            $this->toast('Campagne annulée.');
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    /** Campagne temporaire (non enregistrée) reflétant le formulaire, pour le comptage et l'aperçu. */
    private function draft(): CommCampaign
    {
        $campaign = new CommCampaign([
            'channel' => $this->form['channel'], 'subject' => $this->form['subject'], 'body' => (string) $this->form['body'],
            'audience' => ['types' => $this->form['types'], 'segments' => $this->form['segments'], 'cities' => $this->form['cities'], 'contact_ids' => $this->form['contact_ids'], 'only_opted_in' => $this->form['only_opted_in']],
        ]);
        $campaign->company_id = company_id();
        $campaign->setRelation('company', Tenant::company());

        return $campaign;
    }

    private function audienceCount(): int
    {
        return $this->services()->audience($this->draft())->count();
    }

    public function render()
    {
        $campaigns = CommCampaign::query()->where('company_id', company_id())->with('creator:id,name')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search) !== '', fn ($q) => $q->where('name', 'like', '%'.trim($this->search).'%'))
            ->latest()->get();

        $wizard = [];
        if ($this->showWizard) {
            $draft = $this->draft();
            $wizard = [
                'count' => $this->step >= 2 ? $this->services()->audience($draft)->count() : null,
                'preview' => $this->step >= 3 ? $this->services()->preview($draft, 3) : collect(),
                'cities' => Contact::query()->where('company_id', company_id())->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
                'picked' => Contact::query()->where('company_id', company_id())->whereIn('id', $this->form['contact_ids'] ?: [0])->get(),
                'matches' => $this->searchContacts($this->contactSearch, $this->form['channel']),
                'templates' => $this->templatesFor($this->form['channel']),
            ];
        }

        return view('livewire.communication.campaigns', [
            'campaigns' => $campaigns,
            'counts' => CommCampaign::query()->where('company_id', company_id())->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'wizard' => $wizard,
            'types' => ContactType::options(),
            'segments' => ['b2b' => 'Entreprises (B2B)', 'b2c' => 'Particuliers (B2C)'],
            'channels' => self::channels(),
            'canCampaign' => $this->canCampaign(),
        ]);
    }
}
