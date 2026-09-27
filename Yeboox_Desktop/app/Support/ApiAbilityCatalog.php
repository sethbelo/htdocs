<?php

namespace App\Support;

use App\Models\User;

/** Capacités Sanctum calculées depuis les permissions métier de l'utilisateur. */
final class ApiAbilityCatalog
{
    public static function for(User $user): array
    {
        $abilities = ['profile:read', 'bootstrap:read'];

        self::grant($abilities, $user, ['manage POS', 'manage inventory'], ['catalog:read']);
        self::grant($abilities, $user, ['manage POS', 'manage sales', 'manage reports'], ['sales:read']);
        self::grant($abilities, $user, ['manage POS'], ['sales:write', 'register:read']);
        self::grant($abilities, $user, ['manage register'], ['register:write']);
        self::grant($abilities, $user, ['manage customers'], ['customers:read', 'customers:write']);
        self::grant($abilities, $user, ['manage appointments'], ['appointments:read', 'appointments:write']);
        self::grant($abilities, $user, ['manage inventory'], ['catalog:write', 'stock:read', 'stock:write']);
        self::grant($abilities, $user, ['manage accounting'], ['accounting:read', 'accounting:write']);
        self::grant($abilities, $user, ['manage settings'], ['configuration:read', 'configuration:write']);
        self::grant($abilities, $user, ['manage administration'], ['tenant:read', 'tenant:write']);
        self::grant($abilities, $user, ['manage users'], ['users:read', 'users:write']);

        return array_values(array_unique($abilities));
    }

    private static function grant(array &$abilities, User $user, array $permissions, array $granted): void
    {
        if ($user->canAny($permissions)) {
            array_push($abilities, ...$granted);
        }
    }
}
