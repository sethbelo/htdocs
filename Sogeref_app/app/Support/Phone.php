<?php

namespace App\Support;

/**
 * Liens téléphoniques : tel: (appel) et wa.me (WhatsApp), indicatif RDC
 * (+243) appliqué aux numéros locaux commençant par 0.
 */
class Phone
{
    public static function international(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '243'.substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = '243'.$digits;
        }

        return $digits;
    }

    public static function tel(?string $phone): ?string
    {
        $digits = self::international($phone);

        return strlen($digits) >= 6 ? 'tel:+'.$digits : null;
    }

    public static function whatsapp(?string $phone): ?string
    {
        $digits = self::international($phone);

        return strlen($digits) >= 8 ? 'https://wa.me/'.$digits : null;
    }
}
