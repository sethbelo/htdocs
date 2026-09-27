<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum QuoteType: string
{
    use HasOptions;

    case Quote = 'quote';
    case Proforma = 'proforma';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Devis',
            self::Proforma => 'Facture pro forma',
        };
    }
}
