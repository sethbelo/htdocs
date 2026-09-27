<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InsuranceType: string
{
    use HasOptions;

    case Medical = 'medical';
    case Life = 'life';

    public function label(): string
    {
        return match ($this) {
            self::Medical => 'Soins médicaux',
            self::Life => 'Assurance vie',
        };
    }

    public function color(): string
    {
        return $this === self::Medical ? 'sky' : 'purple';
    }

    public function icon(): string
    {
        return $this === self::Medical ? 'heart' : 'shield-check';
    }
}
