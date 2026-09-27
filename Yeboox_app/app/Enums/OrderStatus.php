<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Confirmed => 'Confirmée',
            self::Preparing => 'En préparation',
            self::Ready => 'Prête',
            self::Served => 'Servie',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Confirmed => 'blue',
            self::Preparing => 'amber',
            self::Ready => 'emerald',
            self::Served => 'teal',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil-square',
            self::Confirmed => 'check-circle',
            self::Preparing => 'fire',
            self::Ready => 'bell-alert',
            self::Served => 'hand-thumb-up',
            self::Completed => 'check-badge',
            self::Cancelled => 'x-circle',
        };
    }
}
