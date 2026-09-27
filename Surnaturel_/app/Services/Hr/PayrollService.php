<?php

namespace App\Services\Hr;

use App\Enums\AttendanceStatus;
use App\Enums\PayrollItemType;
use App\Enums\PayrollPeriodStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Setting;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Moteur de paie :
 * base + primes + heures sup + commissions + indemnités − avances − absences − retenues − autres = net à payer.
 */
class PayrollService
{
    /**
     * @var list<PayrollItemType>
     */
    public const EARNINGS = [PayrollItemType::Bonus, PayrollItemType::Overtime, PayrollItemType::Commission, PayrollItemType::Allowance];

    public function __construct(private NumberGenerator $numbers, private AccountingPoster $poster) {}

    public function compute(PayrollPeriod $period): int
    {
        if (in_array($period->status, [PayrollPeriodStatus::Validated, PayrollPeriodStatus::Paid], true)) {
            throw new BusinessRuleException('Période de paie verrouillée.');
        }

        return DB::transaction(function () use ($period): int {
            $employees = Employee::query()->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('hired_at')->orWhere('hired_at', '<=', $period->ends_on))
                ->get();

            foreach ($employees as $employee) {
                $this->computeFor($period, $employee);
            }

            $period->update(['status' => PayrollPeriodStatus::Computed]);

            return $employees->count();
        });
    }

    public function computeFor(PayrollPeriod $period, Employee $employee): Payslip
    {
        $workingDays = (float) Setting::get('hr.working_days_per_month', 26);
        $attendances = $employee->attendances()->whereDate('date', '>=', $period->starts_on)->whereDate('date', '<=', $period->ends_on)->get();
        $daysWorked = (float) $attendances->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Mission, AttendanceStatus::Leave])->count();
        $absences = (float) $attendances->where('status', AttendanceStatus::Absent)->count();
        $overtimeHours = round($attendances->sum('overtime_minutes') / 60, 2);

        $base = match ($employee->salary_type) {
            'daily' => round($employee->baseSalary() * $daysWorked, 2),
            'hourly' => round($employee->baseSalary() * $attendances->sum('worked_minutes') / 60, 2),
            default => $employee->baseSalary(),
        };

        $lines = [['type' => 'base', 'label' => 'Salaire de base', 'amount' => $base, 'direction' => 'earning']];

        if ($overtimeHours > 0 && $employee->salary_type === 'monthly') {
            $hourlyRate = $base / ($workingDays * (float) Setting::get('hr.daily_hours', 8));
            $lines[] = ['type' => PayrollItemType::Overtime->value, 'label' => "Heures supplémentaires ({$overtimeHours} h)", 'amount' => round($hourlyRate * $overtimeHours * (float) Setting::get('hr.overtime_rate', 1.3), 2), 'direction' => 'earning'];
        }

        if ($absences > 0 && $employee->salary_type === 'monthly') {
            $lines[] = ['type' => PayrollItemType::Absence->value, 'label' => "Absences ({$absences} j)", 'amount' => round($base / $workingDays * $absences, 2), 'direction' => 'deduction'];
        }

        foreach ($period->items()->where('employee_id', $employee->id)->get() as $item) {
            $lines[] = [
                'type' => $item->type->value,
                'label' => $item->label,
                'amount' => (float) $item->amount,
                'direction' => in_array($item->type, self::EARNINGS, true) ? 'earning' : 'deduction',
            ];
        }

        foreach ($employee->loans()->where('status', 'active')->where('balance', '>', 0)->get() as $loan) {
            $lines[] = [
                'type' => $loan->type === 'advance' ? PayrollItemType::Advance->value : PayrollItemType::LoanRepayment->value,
                'label' => $loan->type === 'advance' ? 'Remboursement avance' : 'Remboursement prêt',
                'amount' => min((float) $loan->installment, (float) $loan->balance),
                'direction' => 'deduction',
                'loan_id' => $loan->id,
            ];
        }

        $gross = round(collect($lines)->where('direction', 'earning')->sum('amount'), 2);
        $deductions = round(collect($lines)->where('direction', 'deduction')->sum('amount'), 2);

        return Payslip::query()->updateOrCreate(
            ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
            [
                'number' => Payslip::query()->where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->value('number')
                    ?? $this->numbers->next(NumberGenerator::Payslip),
                'base_salary' => $base,
                'gross_earnings' => $gross,
                'total_deductions' => $deductions,
                'net_pay' => round($gross - $deductions, 2),
                'days_worked' => $daysWorked,
                'overtime_hours' => $overtimeHours,
                'lines' => $lines,
                'dg_status' => 'pending',
                'dg_approved_by' => null,
                'dg_approved_at' => null,
            ],
        );
    }

    /**
     * Validation : verrouille la période, impute les remboursements et comptabilise la paie.
     */
    public function validate(PayrollPeriod $period, User $validator): PayrollPeriod
    {
        if ($period->status !== PayrollPeriodStatus::Computed) {
            throw new BusinessRuleException('Calculez la paie avant de la valider.');
        }

        $pending = $period->payslips()->where('dg_status', '!=', 'approved')->count();
        if ($pending > 0) {
            throw new BusinessRuleException("{$pending} fiche(s) de paie en attente du visa de la Direction générale.");
        }

        return DB::transaction(function () use ($period, $validator): PayrollPeriod {
            foreach ($period->payslips()->with('employee', 'period')->get() as $payslip) {
                foreach ($payslip->lines as $line) {
                    if (isset($line['loan_id'])) {
                        $loan = $payslip->employee->loans()->find($line['loan_id']);
                        $loan?->decrement('balance', $line['amount']);
                        if ($loan && (float) $loan->fresh()->balance <= 0) {
                            $loan->update(['status' => 'repaid']);
                        }
                    }
                }

                if ((float) $payslip->gross_earnings > 0) {
                    $this->poster->postPayslip($payslip);
                }
            }

            $period->update(['status' => PayrollPeriodStatus::Validated, 'validated_by' => $validator->id, 'validated_at' => now()]);

            return $period;
        });
    }

    /**
     * Visa de la Direction générale sur une ou plusieurs fiches de paie (préalable à la validation comptable).
     *
     * @param  iterable<Payslip>  $payslips
     */
    public function approveByDirection(iterable $payslips, User $director): int
    {
        $this->ensureDirector($director);
        $count = 0;

        foreach ($payslips as $payslip) {
            $this->ensureEditable($payslip);
            $payslip->update(['dg_status' => 'approved', 'dg_approved_by' => $director->id, 'dg_approved_at' => now(), 'dg_comment' => null]);
            $count++;
        }

        return $count;
    }

    /**
     * Rejet motivé : la RH corrige les éléments variables puis relance le calcul.
     */
    public function rejectByDirection(Payslip $payslip, User $director, string $comment): Payslip
    {
        $this->ensureDirector($director);
        $this->ensureEditable($payslip);

        if (blank($comment)) {
            throw new BusinessRuleException('Le motif du rejet est obligatoire.');
        }

        $payslip->update(['dg_status' => 'rejected', 'dg_approved_by' => $director->id, 'dg_approved_at' => now(), 'dg_comment' => $comment]);

        return $payslip;
    }

    private function ensureDirector(User $user): void
    {
        if (! $user->can('direction.payroll_sign')) {
            throw new BusinessRuleException('Seule la Direction générale peut viser les fiches de paie.');
        }
    }

    private function ensureEditable(Payslip $payslip): void
    {
        if (in_array($payslip->period->status, [PayrollPeriodStatus::Validated, PayrollPeriodStatus::Paid], true)) {
            throw new BusinessRuleException('Période de paie verrouillée.');
        }
    }
}
