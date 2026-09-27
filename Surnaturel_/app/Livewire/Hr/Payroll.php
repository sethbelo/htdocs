<?php

namespace App\Livewire\Hr;

use App\Enums\PayrollItemType;
use App\Enums\PayrollPeriodStatus;
use App\Exports\TableExport;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\Hr\PayrollService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Paie')]
class Payroll extends Component
{
    use InteractsWithToasts;

    #[Url(as: 'periode')]
    public ?int $periodId = null;

    public string $newPeriod = '';

    /** @var array<string, string> */
    public array $item = ['employee_id' => '', 'type' => 'bonus', 'label' => '', 'amount' => ''];

    /** @var array<string, string> */
    public array $loan = ['employee_id' => '', 'type' => 'advance', 'principal' => '', 'installment' => ''];

    public function mount(): void
    {
        $this->periodId ??= PayrollPeriod::query()->latest('starts_on')->value('id');
        $this->newPeriod = now()->format('Y-m');
    }

    public function createPeriod(): void
    {
        $this->authorize('payroll.create');
        $this->validate(['newPeriod' => ['required', 'date_format:Y-m', 'unique:payroll_periods,code']], [], ['newPeriod' => 'période']);
        $start = Carbon::createFromFormat('Y-m', $this->newPeriod)->startOfMonth();
        $period = PayrollPeriod::query()->create(['code' => $this->newPeriod, 'starts_on' => $start, 'ends_on' => $start->copy()->endOfMonth(), 'status' => PayrollPeriodStatus::Draft]);
        $this->periodId = $period->id;
        $this->toast("Période {$period->code} ouverte.");
    }

    public function addItem(): void
    {
        $this->authorize('payroll.update');
        $data = $this->validate([
            'item.employee_id' => ['required', 'exists:employees,id'],
            'item.type' => ['required', Rule::enum(PayrollItemType::class)],
            'item.label' => ['required', 'string', 'max:255'],
            'item.amount' => ['required', 'numeric', 'gt:0'],
        ])['item'];

        $this->period()->items()->create($data);
        $this->item = ['employee_id' => $data['employee_id'], 'type' => 'bonus', 'label' => '', 'amount' => ''];
        $this->toast('Élément variable ajouté.');
    }

    public function grantLoan(): void
    {
        $this->authorize('payroll.update');
        $data = $this->validate([
            'loan.employee_id' => ['required', 'exists:employees,id'],
            'loan.type' => ['required', 'in:advance,loan'],
            'loan.principal' => ['required', 'numeric', 'gt:0'],
            'loan.installment' => ['required', 'numeric', 'gt:0', 'lte:loan.principal'],
        ])['loan'];

        EmployeeLoan::query()->create($data + ['balance' => $data['principal'], 'granted_on' => today()]);
        $this->reset('loan');
        $this->toast($data['type'] === 'advance' ? 'Avance accordée.' : 'Prêt accordé.');
    }

    public function compute(PayrollService $payroll): void
    {
        $this->authorize('payroll.update');
        $count = $this->attempt(fn () => $payroll->compute($this->period()));

        if ($count !== null) {
            $this->toast("{$count} bulletins calculés.");
        }
    }

    public function validatePeriod(PayrollService $payroll): void
    {
        $this->authorize('payroll.approve');
        $this->attempt(fn () => $payroll->validate($this->period(), auth()->user()), 'Paie validée et comptabilisée.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize('payroll.export');
        $period = $this->period();

        $withBank = auth()->user()->can('employees.view_sensitive');

        $rows = $period->payslips()->with('employee.department')->get()->map(fn (Payslip $p): array => [
            $p->employee->matricule, $p->employee->fullName(), $p->employee->department?->name, $p->employee->employment_status->label(),
            (float) $p->days_worked, (float) $p->overtime_hours, (float) $p->base_salary, (float) $p->gross_earnings, (float) $p->total_deductions, (float) $p->net_pay,
            ...($withBank ? [$p->employee->bank_name, $p->employee->bank_account, $p->employee->mobile_money_number] : []),
        ]);

        $headings = ['Matricule', 'Employé', 'Département', 'Statut', 'Jours', 'HS (h)', 'Base', 'Brut', 'Retenues', 'Net à payer', ...($withBank ? ['Banque', 'Compte', 'Mobile Money'] : [])];

        return Excel::download(new TableExport($headings, $rows, "Paie {$period->code}"), "paie-{$period->code}.xlsx");
    }

    private function period(): PayrollPeriod
    {
        return PayrollPeriod::query()->findOrFail($this->periodId);
    }

    public function render()
    {
        $period = $this->periodId ? PayrollPeriod::query()->with('items.employee')->find($this->periodId) : null;

        return view('livewire.hr.payroll', [
            'periods' => PayrollPeriod::query()->latest('starts_on')->get(),
            'period' => $period,
            'payslips' => $period ? $period->payslips()->with('employee.department')->get() : collect(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'loans' => EmployeeLoan::query()->with('employee')->where('status', 'active')->get(),
            'types' => PayrollItemType::options(),
            'locked' => $period && in_array($period->status, [PayrollPeriodStatus::Validated, PayrollPeriodStatus::Paid], true),
        ]);
    }
}
