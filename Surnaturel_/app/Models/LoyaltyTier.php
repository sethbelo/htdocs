<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['discount_rate' => 'decimal:2', 'points_multiplier' => 'decimal:2'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(LoyaltyAccount::class);
    }
}
