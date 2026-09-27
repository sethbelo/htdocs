<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCard extends Model
{
    use BelongsToCompany;

    protected $attributes = ['points' => 0, 'is_active' => true];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'total_spent' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest('id');
    }
}
