<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasUuid;

    protected $fillable = [
        'code', 'name', 'symbol', 'decimal_places',
        'position', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'decimal_places' => 'integer',
    ];

    // ── Relationships ──────────────────────────────

    public function exchangeRatesFrom()
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function exchangeRatesTo()
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    // ── Scopes ──────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ── Helpers ──────────────────────────────

    /**
     * Format a number according to this currency's rules.
     */
    public function format(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            ',',
            '.'
        );

        return $this->position === 'before'
            ? "{$this->symbol}{$formatted}"
            : "{$formatted} {$this->symbol}";
    }
}
