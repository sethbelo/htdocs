<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'name',
        'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'total',
        'addons', 'comment', 'kitchen_status',
        'sent_to_kitchen_at', 'prepared_at', 'served_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'addons' => 'array',
        'sent_to_kitchen_at' => 'datetime',
        'prepared_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->total = ($item->unit_price * $item->quantity) - $item->discount_amount + $item->tax_amount;
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
