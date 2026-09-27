<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Sale = 'sale';
    case Refund = 'refund';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Vente',
            self::Refund => 'Remboursement',
            self::Deposit => 'Apport',
            self::Withdrawal => 'Retrait',
            self::Expense => 'Dépense',
            self::Transfer => 'Transfert',
        };
    }

    public function isIncoming(): bool
    {
        return match ($this) {
            self::Sale, self::Deposit => true,
            default => false,
        };
    }
}
