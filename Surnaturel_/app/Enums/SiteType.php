<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SiteType: string
{
    use HasOptions;

    case Headquarters = 'headquarters';
    case Branch = 'branch';
    case Shop = 'shop';
    case PointOfSale = 'point_of_sale';

    public function label(): string
    {
        return match ($this) {
            self::Headquarters => 'Siège',
            self::Branch => 'Succursale',
            self::Shop => 'Boutique',
            self::PointOfSale => 'Point de vente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Headquarters => 'indigo',
            self::Branch => 'sky',
            self::Shop => 'emerald',
            self::PointOfSale => 'amber',
        };
    }
}
