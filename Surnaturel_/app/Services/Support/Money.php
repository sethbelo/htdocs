<?php

namespace App\Services\Support;

use App\Models\Setting;

class Money
{
    public static function currency(): string
    {
        return (string) Setting::get('company.currency', 'CDF');
    }

    public static function format(float|int|string|null $amount, ?string $currency = null): string
    {
        $decimals = (int) Setting::get('company.currency_decimals', 2);

        return number_format((float) $amount, $decimals, ',', ' ').' '.($currency ?? self::currency());
    }
}
