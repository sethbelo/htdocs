<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Clés d'URL chiffrées : remplace les identifiants numériques visibles
 * (/commandes/12) par un jeton opaque, stable et infalsifiable (/commandes/Xb3…).
 *
 * Bloc de 16 octets = identifiant (8 octets) + étiquette HMAC-SHA256 tronquée
 * (8 octets), chiffré en AES-128 (un seul bloc) puis encodé en base64url
 * (22 caractères). Les clés AES et HMAC sont dérivées de APP_KEY et d'un sel
 * propre au modèle : le même identifiant donne un jeton différent par modèle,
 * et un jeton d'un modèle ne se décode pas pour un autre.
 */
final class RouteKey
{
    private const CIPHER = 'aes-128-ecb';

    /** @var array<string, array{0: string, 1: string}> */
    private static array $keys = [];

    /** Jeton pour l'identifiant d'un modèle (classe ou instance). */
    public static function encode(Model|string $model, int $id): string
    {
        [$encKey, $macKey] = self::keys($model);
        $payload = pack('J', $id);
        $block = $payload.substr(hash_hmac('sha256', $payload, $macKey, true), 0, 8);
        $cipher = openssl_encrypt($block, self::CIPHER, $encKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=');
    }

    /** Identifiant d'origine, ou null si le jeton est invalide ou falsifié. */
    public static function decode(Model|string $model, mixed $token): ?int
    {
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9_-]{22}$/', $token)) {
            return null;
        }

        $cipher = base64_decode(strtr($token, '-_', '+/').'==', true);
        // Forme canonique uniquement (les 4 bits de remplissage du dernier caractère doivent être nuls).
        if ($cipher === false || strlen($cipher) !== 16 || rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=') !== $token) {
            return null;
        }

        [$encKey, $macKey] = self::keys($model);
        $block = openssl_decrypt($cipher, self::CIPHER, $encKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($block === false || strlen($block) !== 16) {
            return null;
        }

        $payload = substr($block, 0, 8);
        if (! hash_equals(substr(hash_hmac('sha256', $payload, $macKey, true), 0, 8), substr($block, 8))) {
            return null;
        }

        $id = unpack('J', $payload)[1];

        return $id > 0 ? $id : null;
    }

    /** Jeton facultatif (null → chaîne vide), pratique pour les filtres d'URL. */
    public static function encodeOrEmpty(Model|string $model, ?int $id): string
    {
        return $id ? self::encode($model, $id) : '';
    }

    /** @return array{0: string, 1: string} [clé AES-128, clé HMAC] */
    private static function keys(Model|string $model): array
    {
        $salt = $model instanceof Model ? $model::class : ltrim($model, '\\');
        $appKey = (string) config('app.key');

        return self::$keys[$appKey.'|'.$salt] ??= (function () use ($salt, $appKey): array {
            if ($appKey === '') {
                throw new RuntimeException('APP_KEY manquante : impossible de chiffrer les clés d’URL.');
            }
            $secret = str_starts_with($appKey, 'base64:') ? base64_decode(substr($appKey, 7)) : $appKey;
            $material = hash_hmac('sha256', 'sogeref-route-key|'.$salt, $secret, true);

            return [substr($material, 0, 16), hash_hmac('sha256', 'mac|'.$salt, $secret, true)];
        })();
    }
}
