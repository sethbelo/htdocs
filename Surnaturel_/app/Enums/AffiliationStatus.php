<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AffiliationStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspendue',
            self::Terminated => 'Résiliée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Suspended => 'amber',
            self::Terminated => 'red',
        };
    }
}
