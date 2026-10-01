<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Personal\Concerns\ResolvesEmployee;
use App\Models\LeaveRequest;
use App\Models\PersonalNote;
use App\Models\PersonalSheet;
use App\Models\PersonalTask;
use App\Models\Voucher;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Espace personnel — « Ma journée » : tâches du jour, congés en attente,
 * bons à signer et dernières notes.
 */
#[Title('Espace personnel')]
class Overview extends Component
{
    use InteractsWithUi, ResolvesEmployee;

    public string $quickTask = '';

    public function addTask(): void
    {
        $this->validate(['quickTask' => ['required', 'string', 'max:255']], [], ['quickTask' => 'tâche']);
        PersonalTask::create([
            'company_id' => company_id(), 'user_id' => auth()->id(), 'task_date' => today(), 'title' => trim($this->quickTask),
            'position' => (int) PersonalTask::mine()->whereDate('task_date', today())->max('position') + 1,
        ]);
        $this->quickTask = '';
    }

    public function toggleTask(int $id): void
    {
        $task = PersonalTask::mine()->findOrFail($id);
        $this->authorize('update', $task);
        $task->update(['is_done' => ! $task->is_done, 'done_at' => $task->is_done ? null : now()]);
    }

    public function render()
    {
        $user = auth()->user();
        $cid = company_id();
        $employee = $this->employee();
        $canApproveVouchers = $user->canAccessModule('vouchers') && $this->allowed('vouchers.approve');
        $canApproveLeaves = $user->canAccessModule('leaves') && $this->allowed('leaves.approve');
        $tasks = PersonalTask::mine()->whereDate('task_date', today())->ordered()->get();

        return view('livewire.personal.overview', [
            'employee' => $employee,
            'tasks' => $tasks,
            'overdue' => PersonalTask::mine()->whereDate('task_date', '<', today())->where('is_done', false)->count(),
            'myLeaves' => $employee ? LeaveRequest::with('type')->where('company_id', $cid)->where('employee_id', $employee->id)
                ->where(fn ($q) => $q->where('status', 'pending')->orWhere(fn ($q) => $q->where('status', 'approved')->whereDate('end_date', '>=', today())))
                ->orderBy('start_date')->limit(5)->get() : collect(),
            'leavesToApprove' => $canApproveLeaves ? LeaveRequest::where('company_id', $cid)->where('status', 'pending')->count() : null,
            'myVouchers' => Voucher::where('company_id', $cid)->where('requested_by', $user->id)->whereIn('status', ['submitted', 'approved', 'rejected'])->latest('voucher_date')->latest('id')->limit(5)->get(),
            'toSign' => $canApproveVouchers ? Voucher::with('requester')->where('company_id', $cid)->where('status', 'submitted')->latest('voucher_date')->limit(5)->get() : null,
            'toSignCount' => $canApproveVouchers ? Voucher::where('company_id', $cid)->where('status', 'submitted')->count() : 0,
            'notes' => PersonalNote::mine()->orderByDesc('is_pinned')->latest('updated_at')->limit(4)->get(),
            'sheets' => PersonalSheet::mine()->latest('updated_at')->limit(3)->get(['id', 'title', 'rows', 'cols', 'updated_at']),
            'notesCount' => PersonalNote::mine()->count(),
        ]);
    }
}
