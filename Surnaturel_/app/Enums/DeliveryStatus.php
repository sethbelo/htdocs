<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DeliveryStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À livrer',
            self::InProgress => 'En cours',
            self::Delivered => 'Livrée',
            self::Failed => 'Échec',
            self::Postponed => 'Reportée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::InProgress => 'sky',
            self::Delivered => 'emerald',
            self::Failed => 'red',
            self::Postponed => 'amber',
        };
    }
}
