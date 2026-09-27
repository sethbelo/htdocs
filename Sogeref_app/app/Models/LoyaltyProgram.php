<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tiers' => 'array', 'is_active' => 'boolean', 'points_per_unit' => 'decimal:4', 'point_value' => 'decimal:4'];
    }

    public function cards(): HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }

    /** Palier atteint pour un montant cumulé. */
    public function tierFor(float $spent): ?array
    {
        return collect($this->tiers ?? [])->sortByDesc('threshold')->first(fn ($tier) => $spent >= (float) $tier['threshold']);
    }
}
