<?php

namespace App\Models\Concerns;

use App\Support\RouteKey;

/**
 * Remplace l'identifiant numérique par un jeton chiffré dans les URL
 * (route('orders.show', $order) → /commandes/Xb3…) et résout ce jeton
 * lors de la liaison implicite des routes (404 si jeton invalide ou falsifié).
 *
 * Les portées globales et SoftDeletes restent appliquées : seule la valeur
 * recherchée change. Si un champ explicite est demandé ({order:number}),
 * le comportement standard d'Eloquent est conservé.
 */
trait HasEncryptedRouteKey
{
    public function getRouteKey(): mixed
    {
        $key = $this->getKey();

        return $key === null ? null : RouteKey::encode(static::class, (int) $key);
    }

    /** Décode un jeton d'URL vers l'identifiant réel (null si invalide). */
    public static function decodeRouteKey(mixed $token): ?int
    {
        return RouteKey::decode(static::class, $token);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        $id = RouteKey::decode(static::class, $value);

        return $id === null
            ? $query->whereRaw('1 = 0')
            : $query->where($this->qualifyColumn($this->getKeyName()), $id);
    }
}
