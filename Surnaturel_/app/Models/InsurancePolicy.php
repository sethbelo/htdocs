<?php

namespace App\Models;

use App\Enums\InsuranceType;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => InsuranceType::class,
            'coverage_limit' => 'decimal:2',
            'employer_share' => 'decimal:2',
            'monthly_premium' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function affiliations(): HasMany
    {
        return $this->hasMany(Affiliation::class);
    }
}
