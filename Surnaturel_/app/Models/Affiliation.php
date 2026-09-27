<?php

namespace App\Models;

use App\Enums\AffiliationStatus;
use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Affiliation d'un agent (ou d'un ayant droit) à une police santé ou assurance vie.
 */
class Affiliation extends Model
{
    use HasEncryptedRouteKey, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => AffiliationStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'beneficiary_share' => 'decimal:2',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(EmployeeDependent::class, 'employee_dependent_id');
    }

    public function careVouchers(): HasMany
    {
        return $this->hasMany(CareVoucher::class)->latest('issued_on');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberName(): string
    {
        return $this->dependent?->fullName() ?? $this->employee->fullName();
    }

    public function memberRelationship(): string
    {
        return $this->dependent?->relationship->label() ?? 'Agent (titulaire)';
    }

    public function isActive(): bool
    {
        return $this->status === AffiliationStatus::Active && ($this->ends_on === null || $this->ends_on->isFuture() || $this->ends_on->isToday());
    }
}
