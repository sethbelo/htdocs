<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lecture du registre config/erp.php + activation par société.
 */
final class Modules
{
    public static function all(): array
    {
        return config('erp.modules');
    }

    public static function get(string $key): ?array
    {
        return config("erp.modules.{$key}");
    }

    public static function enabled(string $key): bool
    {
        $module = self::get($key);
        if (! $module) {
            return false;
        }
        if ($module['core'] ?? false) {
            return true;
        }

        return ! in_array($key, (array) Tenant::setting('modules_disabled', []), true);
    }

    /** Navigation groupée, filtrée par droits et activation. */
    public static function navigation(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return collect(self::all())
            ->filter(fn ($module, $key) => $user->canAccessModule($key))
            ->map(fn ($module, $key) => $module + ['key' => $key])
            ->groupBy('group')
            ->sortBy(fn ($items, $group) => array_search($group, array_keys(config('erp.groups')), true));
    }

    /** Liste exhaustive des permissions générées depuis le registre. */
    public static function permissions(): array
    {
        $permissions = [];
        foreach (self::all() as $key => $module) {
            $permissions[] = "{$key}.view";
            $permissions[] = "{$key}.manage";
            foreach ($module['actions'] ?? [] as $action) {
                $permissions[] = "{$key}.{$action}";
            }
        }

        return $permissions;
    }
}
