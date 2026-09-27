<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Mode de connexion imposé à chaque utilisateur.
 */
enum LoginMode: string
{
    use HasOptions;

    case Password = 'password';
    case Pin = 'pin';
    case Badge = 'badge';

    public function label(): string
    {
        return match ($this) {
            self::Password => 'Bureau — identifiant et mot de passe',
            self::Pin => 'Caisse / atelier — identifiant et PIN',
            self::Badge => 'Badge — badge et PIN',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Password => 'Bureau',
            self::Pin => 'Caisse / Atelier',
            self::Badge => 'Badge',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Password => 'building-office-2',
            self::Pin => 'calculator',
            self::Badge => 'identification',
        };
    }
}
