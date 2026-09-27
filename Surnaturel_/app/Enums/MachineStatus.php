<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MachineStatus: string
{
    use HasOptions;

    case Operational = 'operational';
    case Maintenance = 'maintenance';
    case Broken = 'broken';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Opérationnelle',
            self::Maintenance => 'En maintenance',
            self::Broken => 'En panne',
            self::Retired => 'Réformée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Operational => 'emerald',
            self::Maintenance => 'amber',
            self::Broken => 'red',
            self::Retired => 'zinc',
        };
    }
}
