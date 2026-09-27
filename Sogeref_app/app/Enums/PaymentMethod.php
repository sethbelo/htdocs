<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    case MobileMoney = 'mobile_money';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Virement bancaire', self::Cash => 'Espèces', self::Card => 'Carte',
            self::MobileMoney => 'Mobile Money', self::Cheque => 'Chèque', self::Other => 'Autre',
        };
    }
}
