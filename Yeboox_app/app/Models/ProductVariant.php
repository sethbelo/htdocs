<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'product_id', 'name', 'sku', 'barcode',
        'selling_price', 'cost_price', 'attributes',
        'stock_quantity', 'image_path', 'is_active',
    ];

    protected $casts = [
        'selling_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'stock_quantity' => 'decimal:4',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/'.$this->image_path)
            : $this->product?->image_url;
    }
}
