<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LoyaltyTransactionType: string
{
    use HasOptions;

    case Earn = 'earn';
    case Redeem = 'redeem';
    case Adjust = 'adjust';
    case Expire = 'expire';

    public function label(): string
    {
        return match ($this) {
            self::Earn => 'Gain',
            self::Redeem => 'Utilisation',
            self::Adjust => 'Ajustement',
            self::Expire => 'Expiration',
        };
    }
}
