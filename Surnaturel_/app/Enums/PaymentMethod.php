<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentMethod: string
{
    use HasOptions;

    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case MobileMoney = 'mobile_money';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::Card => 'Carte',
            self::BankTransfer => 'Virement',
            self::Cheque => 'Chèque',
            self::MobileMoney => 'Mobile Money',
            self::Credit => 'Crédit client',
        };
    }
}
