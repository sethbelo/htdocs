<?php

namespace App\Services\Support;

use Illuminate\Support\Str;

/**
 * Génère la matrice de permissions « module.action » et résout les motifs des rôles.
 */
class PermissionRegistry
{
    /**
     * @return list<string>
     */
    public function all(): array
    {
        $matrix = [];

        foreach (array_keys(config('erp.modules')) as $module) {
            foreach (array_keys(config('erp.actions')) as $action) {
                $matrix[] = "{$module}.{$action}";
            }
        }

        return array_values(array_unique([...$matrix, ...array_keys(config('erp.special_permissions'))]));
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    public function resolve(array $patterns): array
    {
        $all = $this->all();

        return array_values(array_unique(array_merge(...array_map(
            fn (string $pattern): array => array_values(array_filter($all, fn (string $permission): bool => Str::is($pattern, $permission))),
            $patterns,
        ))));
    }

    public function label(string $permission): string
    {
        if ($special = config("erp.special_permissions.{$permission}")) {
            return $special;
        }

        [$module, $action] = explode('.', $permission, 2);

        return config("erp.actions.{$action}", $action).' — '.config("erp.modules.{$module}", $module);
    }
}
