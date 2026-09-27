<?php

namespace App\Models\Concerns;

use App\Support\RouteKey;

/**
 * Les URL exposent un identifiant chiffré et scellé au lieu de l'identifiant numérique.
 */
trait HasEncryptedRouteKey
{
    public function getRouteKey(): mixed
    {
        return RouteKey::encode(static::class, $this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $id = RouteKey::decode(static::class, is_string($value) ? $value : null);

        return $id === null ? null : parent::resolveRouteBinding($id, $this->getKeyName());
    }

    public static function findByRouteKey(?string $token): ?static
    {
        $id = RouteKey::decode(static::class, $token);

        return $id === null ? null : static::query()->find($id);
    }
}
