<?php

namespace App\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';
    case Composite = 'composite';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variable => 'Variable',
            self::Composite => 'Composite',
            self::Service => 'Service',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Simple => 'cube',
            self::Variable => 'squares-2x2',
            self::Composite => 'puzzle-piece',
            self::Service => 'wrench-screwdriver',
        };
    }
}
