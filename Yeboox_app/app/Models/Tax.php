<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'name', 'rate', 'type',
        'is_included', 'applies_to', 'account_id', 'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_included' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate tax amount for a given price.
     */
    public function calculate(float $price): float
    {
        if ($this->type === 'fixed') {
            return (float) $this->rate;
        }

        // Percentage
        if ($this->is_included) {
            // Tax is included: extract it
            return $price - ($price / (1 + ($this->rate / 100)));
        }

        // Tax is excluded: add it
        return $price * ($this->rate / 100);
    }

    /**
     * Get display label with rate.
     */
    public function getLabelAttribute(): string
    {
        if ($this->type === 'fixed') {
            return "{$this->name} ({$this->rate})";
        }

        return "{$this->name} ({$this->rate}%)";
    }
}
