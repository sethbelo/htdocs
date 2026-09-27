<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use Auditable, HasEstablishment, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'first_name', 'last_name', 'company',
        'email', 'phone', 'phone_secondary', 'address', 'city', 'country',
        'nif', 'rccm', 'birth_date', 'gender',
        'loyalty_points', 'loyalty_level', 'total_purchases', 'total_orders',
        'last_purchase_at', 'notes', 'tags', 'is_active', 'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'last_purchase_at' => 'datetime',
        'loyalty_points' => 'integer',
        'total_purchases' => 'decimal:4',
        'total_orders' => 'integer',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    // ── Accessors ────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company ? "{$this->full_name} ({$this->company})" : $this->full_name;
    }

    public function loyaltyCard()
    {
        return $this->hasOne(LoyaltyCard::class);
    }

    // ── Scopes ────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // ── Loyalty ────────────────────────────────
    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
        $this->updateLoyaltyLevel();
    }

    public function deductLoyaltyPoints(int $points): void
    {
        $this->decrement('loyalty_points', min($points, $this->loyalty_points));
    }

    public function updateLoyaltyLevel(): void
    {
        $levels = config('yeboox.loyalty.levels', []);
        $newLevel = 'standard';

        foreach (array_reverse($levels) as $key => $level) {
            if ($this->loyalty_points >= $level['min_points']) {
                $newLevel = $key;
                break;
            }
        }

        if ($this->loyalty_level !== $newLevel) {
            $this->update(['loyalty_level' => $newLevel]);
        }
    }

    public function getLoyaltyLabelAttribute(): string
    {
        $levels = config('yeboox.loyalty.levels', []);

        return $levels[$this->loyalty_level]['label'] ?? 'Standard';
    }

    public function getLoyaltyDiscountAttribute(): float
    {
        $levels = config('yeboox.loyalty.levels', []);

        return $levels[$this->loyalty_level]['discount'] ?? 0;
    }

    // ── Purchase tracking ────────────────────────────────
    public function recordPurchase(float $amount): void
    {
        $this->increment('total_purchases', $amount);
        $this->increment('total_orders');
        $this->update(['last_purchase_at' => now()]);

        // Add loyalty points
        $config = config('yeboox.loyalty');
        $establishment = $this->establishment;
        $enabled = $establishment?->getSetting('loyalty_enabled', $config['enabled']) ?? $config['enabled'];
        $unitAmount = max(1, (int) ($establishment?->getSetting('loyalty_unit_amount', $config['currency_unit_amount']) ?? $config['currency_unit_amount']));
        $pointsPerUnit = max(1, (int) ($establishment?->getSetting('loyalty_points_per_unit', $config['points_per_currency_unit']) ?? $config['points_per_currency_unit']));

        if ($enabled) {
            $points = (int) floor($amount / $unitAmount) * $pointsPerUnit;
            if ($points > 0) {
                $this->addLoyaltyPoints($points);
            }
        }
    }
}
