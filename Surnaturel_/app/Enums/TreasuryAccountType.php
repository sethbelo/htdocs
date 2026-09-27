<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TreasuryAccountType: string
{
    use HasOptions;

    case Cash = 'cash';
    case Bank = 'bank';
    case MobileMoney = 'mobile_money';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Caisse',
            self::Bank => 'Banque',
            self::MobileMoney => 'Mobile Money',
        };
    }
}
