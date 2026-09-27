<?php

namespace App\Enums;

enum EstablishmentType: string
{
    case Restaurant = 'restaurant';
    case Bar = 'bar';
    case Cafe = 'cafe';
    case Snack = 'snack';
    case Hotel = 'hotel';
    case Boutique = 'boutique';
    case Retail = 'retail';
    case Pharmacy = 'pharmacy';
    case Bakery = 'bakery';
    case Spa = 'spa';
    case Salon = 'salon';

    public function label(): string
    {
        return match ($this) {
            self::Restaurant => 'Restaurant',
            self::Bar => 'Bar',
            self::Cafe => 'Café',
            self::Snack => 'Snack',
            self::Hotel => 'Hôtel',
            self::Boutique => 'Boutique',
            self::Retail => 'Commerce de détail',
            self::Pharmacy => 'Pharmacie',
            self::Bakery => 'Boulangerie',
            self::Spa => 'Spa',
            self::Salon => 'Salon de beauté',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Restaurant => 'building-storefront',
            self::Bar => 'beaker',
            self::Cafe => 'cup-soda',
            self::Snack => 'cake',
            self::Hotel => 'building-office-2',
            self::Boutique => 'shopping-bag',
            self::Retail => 'shopping-cart',
            self::Pharmacy => 'plus-circle',
            self::Bakery => 'cake',
            self::Spa => 'sparkles',
            self::Salon => 'scissors',
        };
    }
}
