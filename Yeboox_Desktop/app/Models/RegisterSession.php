<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RegisterSession extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'sync_uuid',
        'opened_at',
        'closed_at',
        'opening_balance',
        'opening_balance_usd',
        'opening_balance_cdf',
        'closing_balance',
        'closing_balance_usd',
        'closing_balance_cdf',
        'exchange_rate',
        'base_currency',
        'secondary_currency',
        'base_to_secondary_rate',
        'cash_sales',
        'mobile_sales',
        'points_sales',
        'status',
        'note',
    ];

    protected static function booted(): void
    {
        static::creating(function (RegisterSession $session): void {
            $session->sync_uuid ??= (string) Str::uuid();
        });
    }

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'opening_balance_usd' => 'decimal:2',
        'opening_balance_cdf' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'closing_balance_usd' => 'decimal:2',
        'closing_balance_cdf' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_to_secondary_rate' => 'decimal:12',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
