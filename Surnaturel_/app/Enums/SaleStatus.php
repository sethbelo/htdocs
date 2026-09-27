<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SaleStatus: string
{
    use HasOptions;

    case Completed = 'completed';
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Terminée',
            self::Voided => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'emerald',
            self::Voided => 'red',
            self::Refunded => 'amber',
        };
    }
}
