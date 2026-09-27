<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PurchaseOrderStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyé',
            self::PartiallyReceived => 'Réception partielle',
            self::Received => 'Réceptionné',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'sky',
            self::PartiallyReceived => 'amber',
            self::Received => 'emerald',
            self::Cancelled => 'red',
        };
    }
}
