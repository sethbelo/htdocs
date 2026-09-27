<?php

/*
|--------------------------------------------------------------------------
| Helpers globaux SOGEREF ERP
|--------------------------------------------------------------------------
*/

use App\Models\Company;
use App\Support\Tenant;

if (! function_exists('company_id')) {
    function company_id(): int
    {
        return Tenant::id();
    }
}

if (! function_exists('company')) {
    function company(): Company
    {
        return Tenant::company();
    }
}

if (! function_exists('erp_setting')) {
    function erp_setting(string $key, mixed $default = null): mixed
    {
        return Tenant::setting($key, $default);
    }
}

if (! function_exists('money')) {
    /** 12 480,50 $ — format monétaire francophone. */
    function money(float|int|string|null $amount, ?string $currency = 'USD', int $decimals = 2): string
    {
        $formatted = number_format((float) $amount, $decimals, ',', ' ');
        $symbol = match ($currency) {
            'USD' => '$', 'CDF' => 'FC', 'EUR' => '€', null, '' => '', default => $currency,
        };

        return trim($formatted.' '.$symbol);
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string|null $value, int $max = 2): string
    {
        $value = (float) $value;

        return number_format($value, fmod($value, 1.0) == 0.0 ? 0 : $max, ',', ' ');
    }
}

if (! function_exists('erp_status')) {
    /** [libellé, ton] d'un statut défini dans config/erp.php. */
    function erp_status(string $map, ?string $value): array
    {
        return config("erp.statuses.{$map}.{$value}", [ucfirst((string) $value), 'neutral']);
    }
}
