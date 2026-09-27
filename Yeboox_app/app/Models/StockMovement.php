<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'warehouse_id', 'product_id', 'variant_id',
        'type', 'reason', 'quantity', 'quantity_before', 'quantity_after',
        'unit_cost', 'reference', 'source_warehouse_id', 'notes',
        'created_by', 'created_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncoming(): bool
    {
        return $this->type === 'in';
    }

    public function isOutgoing(): bool
    {
        return $this->type === 'out';
    }
}
