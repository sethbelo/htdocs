<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline as PipelineModel;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\ContactService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pipeline commercial : opportunités glissées d'étape en étape, montant
 * pondéré par la probabilité, conversion en devis.
 */
#[Title('Pipeline CRM')]
class Pipeline extends Component
{
    use InteractsWithUi;

    public bool $mine = false;

    public bool $showForm = false;

    public ?int $editing = null;

    public array $form = [];

    public bool $newContact = false;

    public array $contact = [];

    public function move(int $id, string $stageId, array $orderedIds = []): void
    {
        $this->authorizeModule('crm', 'manage');
        $opportunity = Opportunity::where('company_id', company_id())->findOrFail($id);
        $stage = PipelineStage::where('pipeline_id', $opportunity->pipeline_id)->findOrFail((int) $stageId);
        $opportunity->update(['stage_id' => $stage->id]);
        foreach ($orderedIds as $position => $oid) {
            Opportunity::where('company_id', company_id())->whereKey($oid)->update(['position' => $position]);
        }
        if ($stage->is_won) {
            $this->toast("Opportunité gagnée : {$opportunity->title}. Créez le devis pour ouvrir le dossier.");
        }
    }

    public function edit(?int $id = null): void
    {
        $this->authorizeModule('crm', 'manage');
        $o = $id ? Opportunity::where('company_id', company_id())->findOrFail($id) : null;
        $pipeline = PipelineModel::where('company_id', company_id())->where('is_default', true)->first();
        $this->editing = $o?->id;
        $this->form = [
            'title' => $o?->title ?? '', 'contact_id' => $o?->contact_id, 'amount' => $o ? (float) $o->amount : 0, 'stage_id' => $o?->stage_id ?? $pipeline?->stages()->orderBy('position')->value('id'),
            'expected_close_at' => $o?->expected_close_at?->toDateString() ?? now()->addMonth()->toDateString(), 'owner_id' => $o?->owner_id ?? auth()->id(), 'notes' => $o?->notes ?? '',
        ];
        // Création rapide du prospect depuis l'opportunité
        $this->newContact = false;
        $this->contact = ['segment' => 'b2c', 'name' => '', 'company' => '', 'phone' => '', 'email' => ''];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(ContactService $contacts): void
    {
        $this->authorizeModule('crm', 'manage');
        if ($this->newContact) {
            $this->form['contact_id'] = $this->createProspect($contacts)?->id;
            if (! $this->form['contact_id']) {
                return;
            }
        }
        $data = $this->validate(['form.title' => ['required', 'string', 'max:200'], 'form.contact_id' => ['required', 'integer'], 'form.amount' => ['numeric', 'min:0'], 'form.stage_id' => ['required', 'integer'], 'form.expected_close_at' => ['nullable', 'date'], 'form.owner_id' => ['nullable', 'integer'], 'form.notes' => ['nullable', 'string', 'max:2000']], [], ['form.title' => 'intitulé', 'form.contact_id' => 'contact'])['form'];
        abort_unless(Contact::where('company_id', company_id())->whereKey($data['contact_id'])->exists(), 422);
        $stage = PipelineStage::findOrFail($data['stage_id']);
        Opportunity::updateOrCreate(['id' => $this->editing], $data + ['company_id' => company_id(), 'pipeline_id' => $stage->pipeline_id, 'currency_code' => 'USD']);
        $this->showForm = false;
        $this->toast('Opportunité enregistrée.');
    }

    /** Prospect saisi dans l'opportunité : réutilise une fiche existante (même téléphone / e-mail) sinon la crée. */
    private function createProspect(ContactService $contacts): ?Contact
    {
        $data = $this->validate([
            'contact.segment' => ['in:b2b,b2c'], 'contact.name' => ['required', 'string', 'max:120'], 'contact.company' => [Rule::requiredIf(($this->contact['segment'] ?? '') === 'b2b'), 'nullable', 'string', 'max:160'],
            'contact.phone' => ['required', 'string', 'max:40'], 'contact.email' => ['nullable', 'email'],
        ], [], ['contact.name' => 'nom du contact', 'contact.company' => 'entreprise', 'contact.phone' => 'téléphone'])['contact'];
        if ($existing = $contacts->duplicates($data['phone'], $data['email'] ?: null, null)->first()) {
            $this->toast("Fiche existante utilisée : {$existing->display_name}.", 'info');

            return $existing;
        }
        [$first, $last] = array_pad(explode(' ', trim($data['name']), 2), 2, null);

        return $contacts->create([
            'type' => 'prospect', 'segment' => $data['segment'], 'company_name' => $data['company'] ?: null, 'first_name' => $last ? $first : null, 'last_name' => $last ?? $first,
            'phone' => $data['phone'], 'email' => $data['email'] ?: null,
        ]);
    }

    public function delete(): void
    {
        $this->authorizeModule('crm', 'manage');
        Opportunity::where('company_id', company_id())->whereKey($this->editing)->delete();
        $this->showForm = false;
    }

    public function render()
    {
        $pipeline = PipelineModel::with(['stages.opportunities' => fn ($q) => $q->with('contact', 'owner')->when($this->mine, fn ($q) => $q->where('owner_id', auth()->id()))])
            ->where('company_id', company_id())->where('is_default', true)->first();
        $open = $pipeline?->stages->reject(fn ($s) => $s->is_won || $s->is_lost)->flatMap->opportunities ?? collect();

        return view('livewire.crm.pipeline', [
            'pipeline' => $pipeline,
            'stats' => [
                'count' => $open->count(),
                'value' => $open->sum('amount'),
                'weighted' => $pipeline?->stages->reject(fn ($s) => $s->is_lost)->sum(fn ($s) => $s->opportunities->sum('amount') * $s->probability / 100) ?? 0,
                'won' => $pipeline?->stages->firstWhere('is_won', true)?->opportunities->sum('amount') ?? 0,
            ],
            'contacts' => Contact::where('company_id', company_id())->whereIn('type', ['client', 'prospect'])->orderByRaw('COALESCE(company_name, last_name)')->get(),
            'users' => User::whereHas('companies', fn ($q) => $q->where('companies.id', company_id()))->orderBy('name')->get(),
        ]);
    }
}
