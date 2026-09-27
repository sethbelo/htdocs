<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Espace d'accueil de chaque utilisateur, déterminé par ses attributions (rôles).
 */
class HomeRoute
{
    public static function name(User $user): string
    {
        foreach (config('erp.roles') as $role => $definition) {
            if ($user->hasRole($role) && Route::has($definition['home'] ?? '') && self::canOpen($user, $definition['home'])) {
                return $definition['home'];
            }
        }

        foreach (Navigation::for($user) as $group) {
            foreach ($group['items'] as $item) {
                if ($item['route'] !== 'home') {
                    return $item['route'];
                }
            }
        }

        return 'profile.pin';
    }

    public static function url(User $user): string
    {
        return route(self::name($user));
    }

    private static function canOpen(User $user, string $routeName): bool
    {
        $route = Route::getRoutes()->getByName($routeName);

        foreach ($route?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'can:') && ! $user->can(substr($middleware, 4))) {
                return false;
            }
        }

        return true;
    }
}
