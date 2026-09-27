<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointOfSale extends Model
{
    use HasFactory;

    protected $table = 'points_of_sale';

    protected $fillable = [
        'company_id', 'establishment_id', 'uuid', 'name', 'code', 'email',
        'phone', 'address', 'default_currency', 'timezone', 'is_active', 'settings',
    ];

    protected $casts = ['is_active' => 'boolean', 'settings' => 'array'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PosSubscription::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('access_level')->withTimestamps();
    }

    public function activeSubscription(): ?PosSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query
                ->where('ends_at', '>=', now())
                ->orWhere('grace_ends_at', '>=', now()))
            ->latest('ends_at')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }
}
