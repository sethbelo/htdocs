<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayrollItemType: string
{
    use HasOptions;

    case Bonus = 'bonus';
    case Overtime = 'overtime';
    case Commission = 'commission';
    case Allowance = 'allowance';
    case Advance = 'advance';
    case LoanRepayment = 'loan_repayment';
    case Absence = 'absence';
    case Deduction = 'deduction';
    case OtherDeduction = 'other_deduction';

    public function label(): string
    {
        return match ($this) {
            self::Bonus => 'Prime',
            self::Overtime => 'Heures supplémentaires',
            self::Commission => 'Commission',
            self::Allowance => 'Indemnité',
            self::Advance => 'Avance',
            self::LoanRepayment => 'Remboursement prêt',
            self::Absence => 'Absence',
            self::Deduction => 'Retenue',
            self::OtherDeduction => 'Autre déduction',
        };
    }
}
