<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProductType: string
{
    use HasOptions;

    case Service = 'service';
    case RawMaterial = 'raw_material';
    case Consumable = 'consumable';
    case Paper = 'paper';
    case Ink = 'ink';
    case Accessory = 'accessory';
    case FinishedGood = 'finished_good';
    case Merchandise = 'merchandise';

    public function label(): string
    {
        return match ($this) {
            self::Service => 'Service d’impression',
            self::RawMaterial => 'Matière première',
            self::Consumable => 'Consommable',
            self::Paper => 'Papier',
            self::Ink => 'Encre',
            self::Accessory => 'Accessoire',
            self::FinishedGood => 'Produit fini',
            self::Merchandise => 'Marchandise boutique',
        };
    }
}
