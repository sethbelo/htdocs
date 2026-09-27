<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceType: string
{
    use HasOptions;

    case Preventive = 'preventive';
    case Corrective = 'corrective';
    case Breakdown = 'breakdown';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Préventive',
            self::Corrective => 'Corrective',
            self::Breakdown => 'Panne',
        };
    }
}
