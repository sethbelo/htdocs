<?php

namespace App\Livewire\Personal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Personal\Concerns\ResolvesEmployee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\HrService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * « Mes congés » : demande de congé (circuit de validation RH existant),
 * solde, historique et annulation des demandes en attente.
 */
#[Title('Mes congés')]
class Leaves extends Component
{
    use InteractsWithUi, ResolvesEmployee;

    public bool $showForm = false;

    public ?int $leaveTypeId = null;

    public string $from = '';

    public string $to = '';

    public string $reason = '';

    public function create(): void
    {
        abort_unless($this->employee(), 403, 'Aucune fiche employé liée à votre compte.');
        $this->resetValidation();
        $this->reset('reason');
        $this->leaveTypeId = LeaveType::where('company_id', company_id())->orderBy('id')->value('id');
        $this->from = now()->addDay()->toDateString();
        $this->to = now()->addDays(5)->toDateString();
        $this->showForm = true;
    }

    public function save(HrService $hr): void
    {
        $employee = $this->employee();
        abort_unless($employee, 403, 'Aucune fiche employé liée à votre compte.');
        $this->validate([
            'leaveTypeId' => ['required', 'integer'],
            'from' => ['required', 'date', 'after_or_equal:today'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], ['from.after_or_equal' => 'La date de début ne peut pas être dans le passé.'], ['leaveTypeId' => 'type', 'from' => 'date de début', 'to' => 'date de fin']);
        $type = LeaveType::where('company_id', company_id())->findOrFail($this->leaveTypeId);

        if ($this->attempt(fn () => $hr->requestLeave($employee, $type, $this->from, $this->to, $this->reason ?: null), 'Demande envoyée aux RH pour validation.')) {
            $this->showForm = false;
        }
    }

    public function cancel(int $id): void
    {
        $employee = $this->employee();
        abort_unless($employee, 403);
        $leave = LeaveRequest::where('company_id', company_id())->where('employee_id', $employee->id)->findOrFail($id);
        if ($leave->status !== 'pending') {
            $this->toast('Seule une demande en attente peut être annulée.', 'error');

            return;
        }
        $leave->update(['status' => 'cancelled', 'decided_at' => now(), 'decision_comment' => 'Annulée par l’employé']);
        $this->toast('Demande annulée.');
    }

    public function render()
    {
        $employee = $this->employee();
        $cid = company_id();
        $history = $employee ? LeaveRequest::with('type', 'approver')->where('company_id', $cid)->where('employee_id', $employee->id)->latest('start_date')->get() : collect();
        $preview = null;
        if ($this->showForm && $this->from && $this->to) {
            try {
                $start = Carbon::parse($this->from);
                $end = Carbon::parse($this->to);
                $preview = $end->gte($start) ? app(HrService::class)->workingDays($start, $end) : null;
            } catch (\Throwable) {
                $preview = null;
            }
        }

        return view('livewire.personal.leaves', [
            'employee' => $employee?->loadMissing('department', 'position'),
            'history' => $history,
            'types' => LeaveType::where('company_id', $cid)->orderBy('id')->get(),
            'pendingDays' => $history->where('status', 'pending')->sum('days'),
            'takenThisYear' => $history->where('status', 'approved')->filter(fn ($l) => $l->start_date->year === now()->year)->sum('days'),
            'upcoming' => $history->where('status', 'approved')->first(fn ($l) => $l->end_date->gte(today())),
            'preview' => $preview,
        ]);
    }
}
