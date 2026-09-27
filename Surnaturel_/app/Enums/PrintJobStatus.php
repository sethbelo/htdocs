<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PrintJobStatus: string
{
    use HasOptions;

    case Queued = 'queued';
    case Preparing = 'preparing';
    case Printing = 'printing';
    case Finishing = 'finishing';
    case QualityCheck = 'quality_check';
    case Ready = 'ready';
    case Rework = 'rework';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'File d’attente',
            self::Preparing => 'Préparation',
            self::Printing => 'Impression',
            self::Finishing => 'Finition',
            self::QualityCheck => 'Contrôle qualité',
            self::Ready => 'Prête à livrer',
            self::Rework => 'À refaire',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'zinc',
            self::Preparing => 'sky',
            self::Printing => 'indigo',
            self::Finishing => 'purple',
            self::QualityCheck => 'amber',
            self::Ready => 'emerald',
            self::Rework => 'red',
        };
    }
}
