<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasEstablishment, HasUuid;

    protected $fillable = [
        'establishment_id', 'name', 'code', 'type', 'icon',
        'is_active', 'requires_reference', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_reference' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getIconClassAttribute(): string
    {
        return match ($this->code) {
            'cash' => 'heroicon-o-banknotes',
            'mobile_money' => 'heroicon-o-device-phone-mobile',
            'card' => 'heroicon-o-credit-card',
            'transfer' => 'heroicon-o-arrows-right-left',
            'check' => 'heroicon-o-document-check',
            default => 'heroicon-o-currency-dollar',
        };
    }
}
