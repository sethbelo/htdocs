<?php

namespace App\Traits;

use App\Models\Establishment;

trait HasEstablishment
{
    /**
     * Boot the trait — auto-scope to current user's establishment.
     */
    protected static function bootHasEstablishment(): void
    {
        static::creating(function ($model) {
            if (empty($model->establishment_id) && auth()->check()) {
                $model->establishment_id = auth()->user()->establishment_id;
            }
        });

        // Global scope to filter by establishment
        static::addGlobalScope('establishment', function ($query) {
            if (auth()->check() && auth()->user()->establishment_id) {
                $table = (new static)->getTable();
                $query->where("{$table}.establishment_id", auth()->user()->establishment_id);
            }
        });
    }

    /**
     * Relationship: belongs to an establishment.
     */
    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    /**
     * Explicit scope — filter by current user's establishment.
     * Useful when the global scope is removed or for explicit scoping.
     */
    public function scopeForEstablishment($query, ?string $establishmentId = null)
    {
        $id = $establishmentId ?? auth()->user()?->establishment_id;

        return $query->where('establishment_id', $id);
    }
}
