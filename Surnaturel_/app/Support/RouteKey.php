<?php

namespace App\Support;

/**
 * Identifiants chiffrés dans les URL : un bloc AES-128 (identifiant + sceau HMAC) encodé en base64url (22 caractères).
 * Une URL modifiée à la main ne correspond à aucun enregistrement (404) et ne révèle ni l'identifiant ni le volume de données.
 */
class RouteKey
{
    public static function encode(string $model, int|string $id): string
    {
        $key = self::key($model);
        $block = pack('J', (int) $id).substr(hash_hmac('sha256', $model.'|'.$id, $key, true), 0, 8);
        $cipher = (string) openssl_encrypt($block, 'aes-128-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        return rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=');
    }

    public static function decode(string $model, ?string $token): ?int
    {
        if ($token === null || ! preg_match('/^[A-Za-z0-9_-]{22}$/', $token)) {
            return null;
        }

        $key = self::key($model);
        $block = openssl_decrypt((string) base64_decode(strtr($token, '-_', '+/').'=='), 'aes-128-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);

        if ($block === false || strlen($block) !== 16) {
            return null;
        }

        $id = unpack('J', substr($block, 0, 8))[1];

        return $id > 0 && hash_equals(substr(hash_hmac('sha256', $model.'|'.$id, $key, true), 0, 8), substr($block, 8)) ? $id : null;
    }

    private static function key(string $model): string
    {
        $appKey = (string) config('app.key');
        $appKey = str_starts_with($appKey, 'base64:') ? base64_decode(substr($appKey, 7)) : $appKey;

        return substr(hash_hmac('sha256', 'route-key|'.$model, $appKey, true), 0, 16);
    }
}
