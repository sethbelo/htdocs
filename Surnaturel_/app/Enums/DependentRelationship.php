<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DependentRelationship: string
{
    use HasOptions;

    case Spouse = 'spouse';
    case Child = 'child';
    case Parent = 'parent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spouse => 'Conjoint(e)',
            self::Child => 'Enfant',
            self::Parent => 'Parent',
            self::Other => 'Autre ayant droit',
        };
    }
}
