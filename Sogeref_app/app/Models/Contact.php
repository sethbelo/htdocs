<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['type' => ContactType::class, 'billing_address' => 'array', 'shipping_address' => 'array', 'meta' => 'array', 'portal_enabled' => 'boolean', 'is_active' => 'boolean', 'discount_rate' => 'decimal:4', 'credit_limit' => 'decimal:2'];
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            $name = trim((string) ($this->company_name ?: "{$this->first_name} {$this->last_name}"));

            return $name !== '' ? $name : ($this->reference ?: 'Contact sans nom');
        });
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn () => collect(preg_split('/\s+/', $this->display_name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function loyaltyCard(): HasOne
    {
        return $this->hasOne(LoyaltyCard::class)->where('is_active', true);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
