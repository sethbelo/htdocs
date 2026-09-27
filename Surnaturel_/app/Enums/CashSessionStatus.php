<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CashSessionStatus: string
{
    use HasOptions;

    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Closed => 'Clôturée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'emerald',
            self::Closed => 'zinc',
        };
    }
}
