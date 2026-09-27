<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ClientType: string
{
    use HasOptions;

    case Individual = 'individual';
    case Company = 'company';
    case Administration = 'administration';
    case Ngo = 'ngo';
    case Reseller = 'reseller';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Particulier',
            self::Company => 'Entreprise',
            self::Administration => 'Administration',
            self::Ngo => 'ONG',
            self::Reseller => 'Revendeur',
        };
    }
}
