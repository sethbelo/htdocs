<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Completed => 'Complété',
            self::Refunded => 'Remboursé',
            self::PartiallyRefunded => 'Partiellement remboursé',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Completed => 'emerald',
            self::Refunded => 'red',
            self::PartiallyRefunded => 'orange',
            self::Cancelled => 'slate',
        };
    }
}
