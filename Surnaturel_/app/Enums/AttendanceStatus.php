<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AttendanceStatus: string
{
    use HasOptions;

    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Mission = 'mission';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Présent',
            self::Late => 'Retard',
            self::Absent => 'Absent',
            self::Mission => 'Mission',
            self::Leave => 'Congé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'emerald',
            self::Late => 'amber',
            self::Absent => 'red',
            self::Mission => 'sky',
            self::Leave => 'purple',
        };
    }
}
