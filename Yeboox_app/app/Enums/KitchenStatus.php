<?php

namespace App\Enums;

enum KitchenStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::Accepted => 'Acceptée',
            self::Preparing => 'En préparation',
            self::Ready => 'Prête',
            self::Served => 'Servie',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'red',
            self::Accepted => 'blue',
            self::Preparing => 'amber',
            self::Ready => 'emerald',
            self::Served => 'teal',
            self::Cancelled => 'slate',
        };
    }
}
