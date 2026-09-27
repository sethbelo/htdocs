<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\DisplayCurrency;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Helper to get a setting value.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever(static::cacheKey($key), function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Helper to set a setting value.
     */
    public static function set(string $key, $value)
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(static::cacheKey($key));
    }

    /** Convertit une valeur de base vers la seule devise secondaire configurée. */
    public static function convert($amount, $exchangeRate)
    {
        return (float) $amount * max(0.00000001, (float) ($exchangeRate ?: DisplayCurrency::rate()));
    }

    private static function cacheKey(string $key): string
    {
        if (! app()->bound(TenantContext::class)) {
            return "setting.global.{$key}";
        }

        $context = app(TenantContext::class);

        return sprintf(
            'setting.company.%s.pos.%s.%s',
            $context->companyId() ?? 'global',
            $context->pointOfSaleId() ?? 'global',
            $key,
        );
    }
}
