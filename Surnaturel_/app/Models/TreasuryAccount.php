<?php

namespace App\Models;

use App\Enums\TreasuryAccountType;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreasuryAccount extends Model
{
    use BelongsToSite, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => TreasuryAccountType::class, 'balance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
