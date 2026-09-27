<?php

namespace App\Livewire\Hr;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\FinancialAccount;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Livewire\Component;

/**
 * Détail d'une période de paie : bulletins, recalcul, validation, paiement.
 */
class PayrollShow extends Component
{
    use InteractsWithUi;

    public PayrollPeriod $period;

    public ?int $accountId = null;

    public ?int $detail = null;

    public function mount(PayrollPeriod $period): void
    {
        abort_unless($period->company_id === company_id(), 404);
        $this->period = $period;
        $this->accountId = FinancialAccount::where('company_id', company_id())->where('type', $period->pay_type === 'daily' ? 'cash' : 'bank')->value('id');
    }

    public function recompute(PayrollService $payroll): void
    {
        $this->authorizeModule('payroll', 'manage');
        $this->attempt(fn () => $payroll->compute($this->period), 'Bulletins recalculés.');
        $this->period->refresh();
    }

    public function validatePeriod(PayrollService $payroll): void
    {
        abort_unless($this->allowed('payroll.validate'), 403);
        $this->attempt(fn () => $payroll->validate($this->period), 'Paie validée et comptabilisée.');
        $this->period->refresh();
    }

    public function pay(PayrollService $payroll): void
    {
        $this->authorizeModule('payroll', 'manage');
        $this->validate(['accountId' => ['required', 'integer']], [], ['accountId' => 'compte de paiement']);
        $this->attempt(fn () => $payroll->markPaid($this->period, $this->accountId), 'Paiement des salaires enregistré.');
        $this->period->refresh();
    }

    public function render()
    {
        $slips = $this->period->payslips()->with('employee.position', 'employee.department')->get()->sortBy('employee.last_name');

        return view('livewire.hr.payroll-show', [
            'slips' => $slips,
            'accounts' => FinancialAccount::where('company_id', company_id())->where('is_active', true)->get(),
            'detailSlip' => $this->detail ? $slips->firstWhere('id', $this->detail) : null,
            'byMethod' => $slips->groupBy(fn ($s) => $s->employee->payment_method)->map->sum('net'),
        ])->title($this->period->reference.' · Paie');
    }
}
