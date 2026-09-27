<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseClaimStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case ManagerApproved = 'manager_approved';
    case FinanceApproved = 'finance_approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'Soumise',
            self::ManagerApproved => 'Validée responsable',
            self::FinanceApproved => 'Validée finance',
            self::Paid => 'Payée',
            self::Rejected => 'Refusée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Submitted => 'sky',
            self::ManagerApproved => 'indigo',
            self::FinanceApproved => 'purple',
            self::Paid => 'emerald',
            self::Rejected => 'red',
        };
    }
}
