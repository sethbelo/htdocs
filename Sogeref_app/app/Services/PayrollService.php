<?php

namespace App\Services;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Models\AttendanceEntry;
use App\Models\BankTransaction;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\SalaryAdvance;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Moteur de paie RDC.
 *
 *   Brut = salaire de base (ou jours présents × taux journalier)
 *        + heures supplémentaires + indemnités de transport / logement.
 *   CNSS ouvrière = 5 % du brut imposable (base + heures sup.).
 *   IPR = barème progressif mensuel en CDF sur (imposable − CNSS),
 *         − 2 % par personne à charge (max 9), plafonné à 30 %.
 *   Net = brut − CNSS − IPR − avances.
 */
class PayrollService
{
    public function __construct(private readonly SequenceService $sequences, private readonly SynchronizeAccounting $accounting) {}

    public function params(): array
    {
        return array_replace(config('erp.payroll'), (array) erp_setting('payroll', []));
    }

    /** (Re)calcule tous les bulletins d'une période. */
    public function compute(PayrollPeriod $period): PayrollPeriod
    {
        if ($period->isLocked()) {
            throw new DomainException('Cette période est validée : elle ne peut plus être recalculée.');
        }

        return DB::transaction(function () use ($period) {
            $employees = $this->eligibleEmployees($period);
            $keep = [];

            foreach ($employees as $employee) {
                $data = $this->computeFor($employee, $period);
                if ($data === null) {
                    continue;
                }
                $slip = Payslip::firstOrNew(['payroll_period_id' => $period->id, 'employee_id' => $employee->id]);
                $slip->fill($data + ['company_id' => $period->company_id, 'status' => 'draft']);
                $slip->reference ??= $this->sequences->next('payslip', $period->company_id);
                $slip->save();
                $keep[] = $slip->id;
            }

            $period->payslips()->whereNotIn('id', $keep)->delete();
            $period->update([
                'status' => 'computed',
                'total_gross' => $period->payslips()->sum('gross'),
                'total_net' => $period->payslips()->sum('net'),
                'total_employer' => $period->payslips()->sum('employer_charges'),
            ]);

            return $period->fresh('payslips');
        });
    }

    public function eligibleEmployees(PayrollPeriod $period)
    {
        return Employee::where('company_id', $period->company_id)
            ->where('pay_type', $period->pay_type)
            ->where('status', 'active')
            ->when($period->site_id, fn ($q) => $q->whereHas('sites', fn ($s) => $s->where('sites.id', $period->site_id)))
            ->orderBy('last_name')
            ->get();
    }

    /** Calcul d'un bulletin (sans persistance). */
    public function computeFor(Employee $employee, PayrollPeriod $period): ?array
    {
        $p = $this->params();
        $rate = max(1, (float) $period->exchange_rate);
        $lines = [];
        $overtimeHours = 0.0;

        if ($employee->isDaily()) {
            // Journaliers : uniquement les listes de présence VALIDÉES de la période.
            $entries = AttendanceEntry::query()
                ->where('employee_id', $employee->id)
                ->whereHas('sheet', fn ($q) => $q->where('status', 'validated')
                    ->whereBetween('work_date', [$period->start_date->toDateString(), $period->end_date->toDateString().' 23:59:59'])
                    ->when($period->site_id, fn ($s) => $s->where('site_id', $period->site_id)))
                ->get();
            if ($entries->isEmpty()) {
                return null;
            }
            $days = round($entries->sum(fn ($e) => $e->dayFraction()), 2);
            $base = round($entries->sum(fn ($e) => $e->dayFraction() * (float) ($e->daily_rate ?: $employee->daily_rate)), 2);
            $overtimeHours = (float) $entries->sum('overtime_hours');
            $hourly = (float) $employee->daily_rate / 8;
            $lines[] = ['code' => 'BASE', 'label' => sprintf('Journées prestées (%s j)', qty($days)), 'type' => 'gain', 'amount' => $base];
        } else {
            $days = (float) $p['working_days'];
            $base = round((float) $employee->base_salary, 2);
            $hourly = $base / max(1, $days * 8);
            $lines[] = ['code' => 'BASE', 'label' => 'Salaire de base', 'type' => 'gain', 'amount' => $base];
        }

        $overtime = round($overtimeHours * $hourly * (float) $p['overtime_multiplier'], 2);
        if ($overtime > 0) {
            $lines[] = ['code' => 'HS', 'label' => sprintf('Heures supplémentaires (%s h)', qty($overtimeHours)), 'type' => 'gain', 'amount' => $overtime];
        }

        $taxableGross = round($base + $overtime, 2);
        $allowances = 0.0;
        foreach (['transport_allowance' => 'Indemnité de transport', 'housing_allowance' => 'Indemnité de logement'] as $field => $label) {
            if ((float) $employee->$field > 0) {
                $amount = $employee->isDaily() ? round((float) $employee->$field / (float) $p['working_days'] * $days, 2) : (float) $employee->$field;
                $allowances += $amount;
                $lines[] = ['code' => strtoupper(substr($field, 0, 4)), 'label' => $label, 'type' => 'allowance', 'amount' => $amount];
            }
        }

        $cnss = round($taxableGross * (float) $p['cnss_employee_rate'] / 100, 2);
        $lines[] = ['code' => 'CNSS', 'label' => sprintf('Cotisation CNSS (Part Ouvrière %s%%)', rtrim(rtrim(number_format((float) $p['cnss_employee_rate'], 2, '.', ''), '0'), '.')), 'type' => 'deduction', 'amount' => $cnss];

        $taxable = round($taxableGross - $cnss, 2);
        $ipr = $this->ipr($taxable, $rate, (int) $employee->dependents, $p);
        $lines[] = ['code' => 'IPR', 'label' => 'Impôt Professionnel sur le Revenu (IPR)', 'type' => 'deduction', 'amount' => $ipr];

        $advances = SalaryAdvance::where('employee_id', $employee->id)->where('status', 'open')->whereDate('granted_at', '<=', $period->end_date)->sum('amount');
        if ($advances > 0) {
            $lines[] = ['code' => 'AVS', 'label' => 'Remboursement avance sur salaire', 'type' => 'deduction', 'amount' => round((float) $advances, 2)];
        }

        $employer = round($taxableGross * ((float) $p['cnss_employer_rate'] + (float) $p['inpp_rate'] + (float) $p['onem_rate']) / 100, 2);
        $lines[] = ['code' => 'PAT', 'label' => 'Charges patronales (CNSS, INPP, ONEM)', 'type' => 'employer', 'amount' => $employer];

        $gross = round($taxableGross + $allowances, 2);

        return [
            'days_worked' => $days,
            'overtime_hours' => $overtimeHours,
            'gross' => $gross,
            'taxable' => $taxable,
            'cnss_employee' => $cnss,
            'ipr' => $ipr,
            'other_deductions' => round((float) $advances, 2),
            'net' => round($gross - $cnss - $ipr - (float) $advances, 2),
            'employer_charges' => $employer,
            'lines' => $lines,
        ];
    }

    /** IPR mensuel : montant imposable en devise de paie → CDF → barème → devise. */
    public function ipr(float $taxable, float $rate, int $dependents, ?array $p = null): float
    {
        $p ??= $this->params();
        $base = $taxable * $rate;
        $tax = 0.0;
        $floor = 0.0;
        foreach ($p['ipr_brackets'] as $bracket) {
            $ceiling = $bracket['up_to'] === null ? INF : (float) $bracket['up_to'];
            if ($base > $floor) {
                $tax += (min($base, $ceiling) - $floor) * (float) $bracket['rate'] / 100;
            }
            $floor = $ceiling;
        }
        $tax *= max(0, 1 - min(9, $dependents) * (float) $p['ipr_dependent_reduction'] / 100);
        $tax = min($tax, $base * (float) $p['ipr_cap_rate'] / 100);

        return round($tax / $rate, 2);
    }

    public function validate(PayrollPeriod $period): void
    {
        if ($period->status !== 'computed') {
            throw new DomainException('Calculez la période avant de la valider.');
        }

        DB::transaction(function () use ($period) {
            $period->payslips()->update(['status' => 'validated']);
            $period->update(['status' => 'validated', 'validated_by' => auth()->id(), 'validated_at' => now()]);

            // Constatation de la charge de personnel (journal OD).
            $slips = $period->payslips;
            $this->accounting->postLines($period, 'OD', $period->end_date->toDateString(), "Paie {$period->name}", array_values(array_filter([
                ['661', 'Rémunérations brutes', $slips->sum('gross'), 0],
                ['664', 'Charges patronales', $slips->sum('employer_charges'), 0],
                ['431', 'CNSS (ouvrière + patronale)', 0, $slips->sum('cnss_employee') + $slips->sum('employer_charges')],
                ['447', 'IPR retenu à la source', 0, $slips->sum('ipr')],
                $slips->sum('other_deductions') > 0 ? ['422', 'Avances récupérées', 0, $slips->sum('other_deductions')] : null,
                ['421', 'Net à payer au personnel', 0, $slips->sum('net')],
            ])));

            SalaryAdvance::whereIn('employee_id', $slips->pluck('employee_id'))->where('status', 'open')
                ->whereDate('granted_at', '<=', $period->end_date)->update(['status' => 'deducted']);
        });
    }

    public function markPaid(PayrollPeriod $period, int $financialAccountId): void
    {
        if ($period->status !== 'validated') {
            throw new DomainException('Seule une période validée peut être payée.');
        }

        DB::transaction(function () use ($period, $financialAccountId) {
            $account = FinancialAccount::findOrFail($financialAccountId);
            $net = (float) $period->payslips()->sum('net');
            $treasury = match ($account->type) {
                'cash' => '571', 'mobile_money' => '581', default => '521'
            };

            $period->payslips()->update(['status' => 'paid', 'paid_at' => now()]);
            $period->update(['status' => 'paid', 'paid_at' => now()]);

            $payment = BankTransaction::create([
                'financial_account_id' => $account->id, 'transaction_date' => now()->toDateString(), 'reference' => $period->reference,
                'description' => "Paiement salaires · {$period->name}", 'amount' => -$net, 'status' => 'reconciled',
                'reconcilable_type' => $period->getMorphClass(), 'reconcilable_id' => $period->id,
            ]);
            $this->accounting->postLines($payment, $account->type === 'cash' ? 'CA' : 'BQ', now()->toDateString(), "Règlement salaires {$period->name}", [
                ['421', 'Salaires nets versés', $net, 0],
                [$treasury, 'Sortie de trésorerie', 0, $net],
            ], $period->company_id);
        });
    }
}
