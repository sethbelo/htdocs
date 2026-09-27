<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        [$group, $name] = str_contains($key, '.') ? explode('.', $key, 2) : ['general', $key];

        $all = Cache::rememberForever('settings', fn () => static::query()->get()
            ->mapWithKeys(fn (self $setting): array => ["{$setting->group}.{$setting->key}" => $setting->value])
            ->all());

        return $all["{$group}.{$name}"] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        [$group, $name] = str_contains($key, '.') ? explode('.', $key, 2) : ['general', $key];

        static::query()->updateOrCreate(['group' => $group, 'key' => $name], ['value' => $value]);
        Cache::forget('settings');
    }
}
