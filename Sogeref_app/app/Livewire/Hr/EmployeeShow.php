<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\AttendanceEntry;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\SalaryAdvance;
use App\Services\HrService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dossier individuel : informations, présences, congés, paie, avances, chantiers.
 */
class EmployeeShow extends Component
{
    use InteractsWithUi;

    public Employee $employee;

    #[Url(as: 'onglet')]
    public string $tab = 'infos';

    public ?int $leaveTypeId = null;

    public string $leaveFrom = '';

    public string $leaveTo = '';

    public string $leaveReason = '';

    public string $advanceAmount = '';

    public string $advanceNote = '';

    public function mount(Employee $employee): void
    {
        abort_unless($employee->company_id === company_id(), 404);
        $this->employee = $employee;
        $this->leaveTypeId = LeaveType::where('company_id', company_id())->value('id');
        $this->leaveFrom = now()->addDay()->toDateString();
        $this->leaveTo = now()->addDays(5)->toDateString();
    }

    public function requestLeave(HrService $hr): void
    {
        $this->authorizeModule('leaves', 'manage');
        $this->validate(['leaveTypeId' => ['required', 'integer'], 'leaveFrom' => ['required', 'date'], 'leaveTo' => ['required', 'date', 'after_or_equal:leaveFrom']]);
        $type = LeaveType::where('company_id', company_id())->findOrFail($this->leaveTypeId);
        $this->attempt(fn () => $hr->requestLeave($this->employee, $type, $this->leaveFrom, $this->leaveTo, $this->leaveReason ?: null), 'Demande de congé enregistrée.');
        $this->leaveReason = '';
    }

    public function addAdvance(): void
    {
        $this->authorizeModule('payroll', 'manage');
        $this->validate(['advanceAmount' => ['required', 'numeric', 'gt:0']], [], ['advanceAmount' => 'montant']);
        SalaryAdvance::create(['company_id' => company_id(), 'employee_id' => $this->employee->id, 'amount' => $this->advanceAmount, 'granted_at' => now(), 'note' => $this->advanceNote ?: null]);
        $this->reset(['advanceAmount', 'advanceNote']);
        $this->toast('Avance enregistrée : elle sera retenue sur la prochaine paie.');
    }

    public function render()
    {
        $this->employee->load('department', 'position', 'user', 'sites');
        $attendance = AttendanceEntry::with('sheet.site')->where('employee_id', $this->employee->id)->whereHas('sheet', fn ($q) => $q->where('work_date', '>=', now()->subDays(45)))->get()->sortByDesc(fn ($e) => $e->sheet->work_date);

        return view('livewire.hr.employee-show', [
            'attendance' => $attendance,
            'leaves' => $this->employee->leaves()->with('type', 'approver')->latest('start_date')->get(),
            'payslips' => $this->employee->payslips()->with('period')->latest('id')->get(),
            'advances' => $this->employee->advances()->latest('granted_at')->get(),
            'leaveTypes' => LeaveType::where('company_id', company_id())->get(),
            'monthDays' => $attendance->filter(fn ($e) => $e->sheet->work_date->isCurrentMonth())->sum(fn ($e) => $e->dayFraction()),
        ])->title($this->employee->full_name.' · Employés');
    }
}
