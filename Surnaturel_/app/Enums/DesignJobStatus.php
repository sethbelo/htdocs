<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DesignJobStatus: string
{
    use HasOptions;

    case ToDesign = 'to_design';
    case InProgress = 'in_progress';
    case Proposal = 'proposal';
    case Correction = 'correction';
    case Proof = 'proof';
    case Validated = 'validated';
    case SentToPrint = 'sent_to_print';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ToDesign => 'À concevoir',
            self::InProgress => 'En conception',
            self::Proposal => 'Proposition',
            self::Correction => 'Correction',
            self::Proof => 'BAT',
            self::Validated => 'Validée',
            self::SentToPrint => 'Transmise impression',
            self::Blocked => 'Impossibilité signalée',
            self::Cancelled => 'Intervention annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ToDesign => 'zinc',
            self::InProgress => 'sky',
            self::Proposal => 'indigo',
            self::Correction => 'amber',
            self::Proof => 'purple',
            self::Validated => 'emerald',
            self::SentToPrint => 'teal',
            self::Blocked => 'red',
            self::Cancelled => 'red',
        };
    }
}
