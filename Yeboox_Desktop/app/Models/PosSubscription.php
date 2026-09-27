<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'point_of_sale_id', 'plan_code', 'status', 'starts_at',
        'ends_at', 'grace_ends_at', 'amount', 'currency', 'billing_period',
        'max_users', 'features', 'external_reference',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'amount' => 'decimal:2',
        'max_users' => 'integer',
        'features' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, ['active', 'trial'], true)
            && $this->starts_at->lte(now())
            && ($this->ends_at->gte(now()) || $this->grace_ends_at?->gte(now()));
    }
}
