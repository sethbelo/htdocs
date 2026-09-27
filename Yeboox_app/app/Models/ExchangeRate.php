<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'from_currency_id', 'to_currency_id',
        'buy_rate', 'sell_rate', 'effective_date', 'created_by',
    ];

    protected $casts = [
        'buy_rate' => 'decimal:6',
        'sell_rate' => 'decimal:6',
        'effective_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────

    /**
     * Get the latest rate for a currency pair.
     */
    public function scopeLatestRate($query, string $fromId, string $toId)
    {
        return $query->where('from_currency_id', $fromId)
            ->where('to_currency_id', $toId)
            ->orderBy('effective_date', 'desc')
            ->limit(1);
    }

    /**
     * Get rates effective on a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc');
    }
}
