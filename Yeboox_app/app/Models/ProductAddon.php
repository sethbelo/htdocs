<?php

namespace App\Models;

use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAddon extends Model
{
    use HasEstablishment, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'name', 'price', 'category', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_addon_product');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
