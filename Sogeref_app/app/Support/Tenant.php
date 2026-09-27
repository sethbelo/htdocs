<?php

namespace App\Support;

use App\Models\Company;

/**
 * Société courante de l'utilisateur connecté (mise en cache par requête).
 */
final class Tenant
{
    private static ?Company $company = null;

    public static function id(): int
    {
        return (int) (auth()->user()?->current_company_id ?: session('company_id') ?: Company::query()->value('id'));
    }

    public static function company(): Company
    {
        if (! self::$company || self::$company->id !== self::id()) {
            self::$company = Company::query()->findOrFail(self::id());
        }

        return self::$company;
    }

    public static function flush(): void
    {
        self::$company = null;
    }

    /** Paramètre stocké dans companies.settings (notation pointée). */
    public static function setting(string $key, mixed $default = null): mixed
    {
        return data_get(self::company()->settings, $key, $default);
    }

    public static function putSetting(string $key, mixed $value): void
    {
        $company = self::company();
        $settings = $company->settings ?? [];
        data_set($settings, $key, $value);
        $company->update(['settings' => $settings]);
    }
}
