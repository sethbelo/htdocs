<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait — auto-generate UUID on creating.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Indicate that the ID is not auto-incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Indicate the key type is string.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
