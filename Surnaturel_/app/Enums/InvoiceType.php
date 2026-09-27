<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoiceType: string
{
    use HasOptions;

    case Invoice = 'invoice';
    case Deposit = 'deposit';
    case CreditNote = 'credit_note';
    case Receipt = 'receipt';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Facture',
            self::Deposit => 'Facture d’acompte',
            self::CreditNote => 'Avoir',
            self::Receipt => 'Reçu',
        };
    }
}
