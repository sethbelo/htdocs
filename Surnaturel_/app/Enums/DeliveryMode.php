<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DeliveryMode: string
{
    use HasOptions;

    case Pickup = 'pickup';
    case Local = 'local';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Retrait au siège',
            self::Local => 'Livraison locale',
            self::External => 'Livraison externe',
        };
    }
}
