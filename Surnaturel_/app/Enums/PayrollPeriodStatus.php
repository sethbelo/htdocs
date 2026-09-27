<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayrollPeriodStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Computed = 'computed';
    case Validated = 'validated';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Computed => 'Calculée',
            self::Validated => 'Validée',
            self::Paid => 'Payée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Computed => 'sky',
            self::Validated => 'emerald',
            self::Paid => 'indigo',
        };
    }
}
