<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToTenant, HasFactory;

    public function tenantScopeLevel(): string
    {
        return 'company';
    }

    protected $fillable = [
        'name',
        'phone',
        'email',
        'points',
        'loyalty_service_count',
        'loyalty_reward_balance',
        'is_active',
    ];

    protected $casts = [
        'points' => 'integer',
        'loyalty_service_count' => 'integer',
        'loyalty_reward_balance' => 'integer',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
