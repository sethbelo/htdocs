<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug', 'name', 'eyebrow', 'tagline', 'monthly_price', 'annual_price',
        'setup_fee', 'currency', 'max_locations', 'features', 'cta_label',
        'is_highlighted', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'features' => 'array',
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
    ];
}
