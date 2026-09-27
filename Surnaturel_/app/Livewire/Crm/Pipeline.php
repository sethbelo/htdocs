<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pipeline commercial')]
class Pipeline extends Component
{
    use InteractsWithToasts;

    public bool $mineOnly = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $lostReason = '';

    /**
     * Glisser-déposer : changement d'étape et/ou d'ordre dans la colonne.
     */
    public function move(int $opportunityId, int $position, int|string $stageId): void
    {
        $this->authorize('pipeline.update');
        $opportunity = Opportunity::query()->visibleTo(auth()->user())->findOrFail($opportunityId);
        $stage = PipelineStage::query()->findOrFail((int) $stageId);

        DB::transaction(function () use ($opportunity, $stage, $position): void {
            $ids = $stage->opportunities()->whereKeyNot($opportunity->id)->pluck('id')->all();
            array_splice($ids, $position, 0, [$opportunity->id]);

            foreach ($ids as $index => $id) {
                Opportunity::query()->whereKey($id)->update(['position' => $index, 'pipeline_stage_id' => $stage->id]);
            }
        });

        if ($stage->is_lost && ! $opportunity->lost_reason) {
            $this->editingId = $opportunity->id;
            $this->modal('lost')->show();
        }
    }

    public function saveLostReason(): void
    {
        $this->validate(['lostReason' => ['required', 'string', 'max:255']], [], ['lostReason' => 'motif']);
        Opportunity::query()->findOrFail($this->editingId)->update(['lost_reason' => $this->lostReason]);
        $this->reset('lostReason', 'editingId');
        $this->modal('lost')->close();
    }

    public function create(?int $stageId = null): void
    {
        $this->authorize('pipeline.create');
        $this->editingId = null;
        $this->form = ['title' => '', 'client_id' => '', 'prospect_name' => '', 'prospect_phone' => '', 'prospect_email' => '', 'estimated_amount' => '', 'expected_close_date' => '', 'needs' => '', 'pipeline_stage_id' => $stageId ?? PipelineStage::query()->orderBy('position')->value('id')];
        $this->modal('opportunity')->show();
    }

    public function edit(int $id): void
    {
        $opportunity = Opportunity::query()->visibleTo(auth()->user())->findOrFail($id);
        $this->editingId = $id;
        $this->form = $opportunity->only(['title', 'client_id', 'prospect_name', 'prospect_phone', 'prospect_email', 'estimated_amount', 'needs', 'pipeline_stage_id'])
            + ['expected_close_date' => $opportunity->expected_close_date?->toDateString()];
        $this->modal('opportunity')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'pipeline.update' : 'pipeline.create');

        $data = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.client_id' => ['nullable', 'exists:clients,id'],
            'form.prospect_name' => ['required_without:form.client_id', 'nullable', 'string', 'max:255'],
            'form.prospect_phone' => ['nullable', 'string', 'max:30'],
            'form.prospect_email' => ['nullable', 'email'],
            'form.estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'form.expected_close_date' => ['nullable', 'date'],
            'form.needs' => ['nullable', 'string', 'max:3000'],
            'form.pipeline_stage_id' => ['required', 'exists:pipeline_stages,id'],
        ], [], ['form.title' => 'intitulé', 'form.prospect_name' => 'prospect'])['form'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $data['estimated_amount'] ??= 0;

        if ($this->editingId) {
            Opportunity::query()->findOrFail($this->editingId)->update($data);
        } else {
            Opportunity::query()->create($data + ['sales_rep_id' => auth()->id(), 'site_id' => auth()->user()->default_site_id, 'position' => 999]);
        }

        $this->modal('opportunity')->close();
        $this->toast('Opportunité enregistrée.');
    }

    public function render()
    {
        $stages = PipelineStage::query()->orderBy('position')->with(['opportunities' => fn ($q) => $q->visibleTo(auth()->user())
            ->when($this->mineOnly, fn ($q) => $q->where('sales_rep_id', auth()->id()))
            ->with('client', 'salesRep')])->get();

        return view('livewire.crm.pipeline', [
            'stages' => $stages,
            'clients' => Client::query()->orderBy('name')->limit(500)->get(['id', 'name', 'company_name']),
        ]);
    }
}
