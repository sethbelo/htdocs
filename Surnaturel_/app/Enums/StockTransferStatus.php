<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockTransferStatus: string
{
    use HasOptions;

    case Requested = 'requested';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::InTransit => 'En transit',
            self::Received => 'Réceptionné',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::InTransit => 'sky',
            self::Received => 'emerald',
            self::Cancelled => 'zinc',
        };
    }
}
