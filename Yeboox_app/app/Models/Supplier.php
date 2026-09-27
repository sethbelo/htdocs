<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, HasEstablishment, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'name', 'contact_name', 'email', 'phone',
        'phone_secondary', 'address', 'city', 'country',
        'nif', 'rccm', 'bank_name', 'bank_account',
        'payment_terms_days', 'notes', 'total_purchases', 'total_debt',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'total_purchases' => 'decimal:4',
        'total_debt' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

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
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }
}
