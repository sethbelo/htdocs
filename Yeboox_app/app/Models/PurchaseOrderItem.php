<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'variant_id',
        'quantity_ordered', 'quantity_received', 'unit_cost',
        'tax_rate', 'discount_percent', 'total', 'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $discount = $item->unit_cost * $item->quantity_ordered * ($item->discount_percent / 100);
            $item->total = ($item->unit_cost * $item->quantity_ordered) - $discount;
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return (float) $this->quantity_ordered - (float) $this->quantity_received;
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }
}
