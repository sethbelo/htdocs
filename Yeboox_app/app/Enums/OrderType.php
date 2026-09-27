<?php

namespace App\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case Takeaway = 'takeaway';
    case Delivery = 'delivery';
    case Reservation = 'reservation';
    case Deferred = 'deferred';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => 'Sur place',
            self::Takeaway => 'À emporter',
            self::Delivery => 'Livraison',
            self::Reservation => 'Réservation',
            self::Deferred => 'Différée',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DineIn => 'home',
            self::Takeaway => 'shopping-bag',
            self::Delivery => 'truck',
            self::Reservation => 'calendar',
            self::Deferred => 'clock',
        };
    }
}
