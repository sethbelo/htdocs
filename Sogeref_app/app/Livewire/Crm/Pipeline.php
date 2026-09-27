<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline as PipelineModel;
use App\Models\PipelineStage;
use App\Models\User;
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
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeModule('crm', 'manage');
        $data = $this->validate(['form.title' => ['required', 'string', 'max:200'], 'form.contact_id' => ['required', 'integer'], 'form.amount' => ['numeric', 'min:0'], 'form.stage_id' => ['required', 'integer'], 'form.expected_close_at' => ['nullable', 'date'], 'form.owner_id' => ['nullable', 'integer'], 'form.notes' => ['nullable', 'string', 'max:2000']], [], ['form.title' => 'intitulé', 'form.contact_id' => 'contact'])['form'];
        $stage = PipelineStage::findOrFail($data['stage_id']);
        Opportunity::updateOrCreate(['id' => $this->editing], $data + ['company_id' => company_id(), 'pipeline_id' => $stage->pipeline_id, 'currency_code' => 'USD']);
        $this->showForm = false;
        $this->toast('Opportunité enregistrée.');
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
