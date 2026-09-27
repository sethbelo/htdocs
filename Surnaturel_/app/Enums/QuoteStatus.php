<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum QuoteStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyé',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
            self::Expired => 'Expiré',
            self::Converted => 'Converti en commande',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'sky',
            self::Accepted => 'emerald',
            self::Rejected => 'red',
            self::Expired => 'zinc',
            self::Converted => 'indigo',
        };
    }
}
