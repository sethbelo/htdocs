<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CashMovementType: string
{
    use HasOptions;

    case Opening = 'opening';
    case Sale = 'sale';
    case OrderPayment = 'order_payment';
    case Disbursement = 'disbursement';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Ouverture',
            self::Sale => 'Vente POS',
            self::OrderPayment => 'Encaissement commande',
            self::Disbursement => 'Décaissement',
            self::Deposit => 'Apport',
            self::Withdrawal => 'Retrait/versement',
            self::Refund => 'Remboursement',
            self::Adjustment => 'Ajustement',
        };
    }
}
