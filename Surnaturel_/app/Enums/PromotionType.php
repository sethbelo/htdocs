<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PromotionType: string
{
    use HasOptions;

    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Pourcentage',
            self::Fixed => 'Montant fixe',
        };
    }
}
