<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\HrService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Congés : demandes, validation hiérarchique, soldes et calendrier des absences.
 */
#[Title('Congés')]
class Leaves extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url(as: 'statut')]
    public string $status = 'pending';

    public string $month = '';

    public bool $showForm = false;

    public ?int $employeeId = null;

    public ?int $leaveTypeId = null;

    public string $from = '';

    public string $to = '';

    public string $reason = '';

    public ?int $deciding = null;

    public string $comment = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function create(): void
    {
        $this->authorizeModule('leaves', 'manage');
        $this->reset(['employeeId', 'reason']);
        $this->leaveTypeId = LeaveType::where('company_id', company_id())->value('id');
        $this->from = now()->addDay()->toDateString();
        $this->to = now()->addDays(5)->toDateString();
        $this->showForm = true;
    }

    public function save(HrService $hr): void
    {
        $this->authorizeModule('leaves', 'manage');
        $this->validate(['employeeId' => ['required', 'integer'], 'leaveTypeId' => ['required', 'integer'], 'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']], [], ['employeeId' => 'employé', 'leaveTypeId' => 'type']);
        $employee = Employee::where('company_id', company_id())->findOrFail($this->employeeId);
        $type = LeaveType::where('company_id', company_id())->findOrFail($this->leaveTypeId);
        if ($this->attempt(fn () => $hr->requestLeave($employee, $type, $this->from, $this->to, $this->reason ?: null), 'Demande enregistrée.')) {
            $this->showForm = false;
        }
    }

    public function decide(int $id, bool $approve): void
    {
        $hr = app(HrService::class);
        abort_unless($this->allowed('leaves.approve'), 403);
        $leave = LeaveRequest::with('employee', 'type')->where('company_id', company_id())->findOrFail($id);
        $this->attempt(fn () => $hr->decideLeave($leave, $approve, $this->comment ?: null), $approve ? 'Congé approuvé.' : 'Demande refusée.');
        $this->comment = '';
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonthsNoOverflow($delta)->format('Y-m');
    }

    public function render()
    {
        $cid = company_id();
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $monthLeaves = LeaveRequest::with('employee', 'type')->where('company_id', $cid)->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->get();

        return view('livewire.hr.leaves', [
            'requests' => LeaveRequest::with('employee.department', 'type', 'approver')->where('company_id', $cid)
                ->when($this->status, fn ($q) => $q->where('status', $this->status))->latest('start_date')->paginate(15),
            'counts' => LeaveRequest::where('company_id', $cid)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'days' => collect(range(0, $start->daysInMonth - 1))->map(fn ($d) => $start->copy()->addDays($d)),
            'calendar' => $monthLeaves->groupBy('employee_id'),
            'monthStart' => $start,
            'onLeaveToday' => LeaveRequest::with('employee')->where('company_id', $cid)->where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->get(),
            'employees' => Employee::where('company_id', $cid)->where('status', 'active')->orderBy('last_name')->get(),
            'types' => LeaveType::where('company_id', $cid)->get(),
        ]);
    }
}
