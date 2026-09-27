<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashRegister extends Model
{
    use BelongsToSite;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'requires_pin' => 'boolean',
            'is_active' => 'boolean',
            'max_disbursement_without_approval' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(CashSession::class)->where('status', CashSessionStatus::Open)->latestOfMany();
    }
}
