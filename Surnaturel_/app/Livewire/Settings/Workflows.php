<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\ApprovalStep;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Paramètres — Circuits de validation')]
class Workflows extends Component
{
    use InteractsWithToasts;

    /** @var array<int, array<string, mixed>> */
    public array $steps = [];

    /** @var array<int, array<string, string>> */
    public array $newStep = [];

    public function mount(): void
    {
        $this->loadSteps();
    }

    private function loadSteps(): void
    {
        $this->steps = ApprovalStep::query()->get()->mapWithKeys(fn (ApprovalStep $s) => [$s->id => [
            'name' => $s->name, 'approver_role' => (string) $s->approver_role, 'approver_user_id' => (string) $s->approver_user_id, 'min_amount' => (string) (float) $s->min_amount,
        ]])->all();
    }

    public function saveStep(int $id): void
    {
        $this->authorize('approvals.update');
        $this->validate([
            "steps.{$id}.name" => ['required', 'string', 'max:255'],
            "steps.{$id}.approver_role" => ['nullable', 'exists:roles,name', "required_without:steps.{$id}.approver_user_id"],
            "steps.{$id}.approver_user_id" => ['nullable', 'exists:users,id'],
            "steps.{$id}.min_amount" => ['required', 'numeric', 'min:0'],
        ]);

        ApprovalStep::query()->findOrFail($id)->update(array_map(fn ($v) => $v === '' ? null : $v, $this->steps[$id]));
        $this->toast('Étape enregistrée.');
    }

    public function addStep(int $workflowId): void
    {
        $this->authorize('approvals.update');
        $data = $this->newStep[$workflowId] ?? [];
        $this->validate([
            "newStep.{$workflowId}.name" => ['required', 'string', 'max:255'],
            "newStep.{$workflowId}.approver_role" => ['required', 'exists:roles,name'],
            "newStep.{$workflowId}.min_amount" => ['nullable', 'numeric', 'min:0'],
        ], [], ["newStep.{$workflowId}.name" => 'nom de l’étape', "newStep.{$workflowId}.approver_role" => 'rôle']);

        $workflow = ApprovalWorkflow::query()->findOrFail($workflowId);
        $workflow->steps()->create(['sequence' => (int) $workflow->steps()->max('sequence') + 1, 'name' => $data['name'], 'approver_role' => $data['approver_role'], 'min_amount' => (float) ($data['min_amount'] ?? 0)]);
        unset($this->newStep[$workflowId]);
        $this->loadSteps();
        $this->toast('Étape ajoutée.');
    }

    public function removeStep(int $id): void
    {
        $this->authorize('approvals.update');
        ApprovalStep::query()->whereKey($id)->delete();
        $this->loadSteps();
    }

    public function toggle(int $workflowId): void
    {
        $this->authorize('approvals.update');
        $workflow = ApprovalWorkflow::query()->findOrFail($workflowId);
        $workflow->update(['is_active' => ! $workflow->is_active]);
    }

    public function render()
    {
        return view('livewire.settings.workflows', [
            'workflows' => ApprovalWorkflow::query()->with('steps')->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
