<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryTransfer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transferred_at' => 'datetime'];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'from_treasury_account_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'to_treasury_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
