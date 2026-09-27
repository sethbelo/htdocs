<?php

namespace App\Enums;

enum TableStatus: string
{
    case Free = 'free';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Ordering = 'ordering';
    case Served = 'served';
    case BillRequested = 'bill_requested';
    case Cleaning = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Libre',
            self::Reserved => 'Réservée',
            self::Occupied => 'Occupée',
            self::Ordering => 'Commande en cours',
            self::Served => 'Servie',
            self::BillRequested => 'Addition demandée',
            self::Cleaning => 'À nettoyer',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Free => 'emerald',
            self::Reserved => 'purple',
            self::Occupied => 'blue',
            self::Ordering => 'amber',
            self::Served => 'teal',
            self::BillRequested => 'orange',
            self::Cleaning => 'slate',
        };
    }
}
