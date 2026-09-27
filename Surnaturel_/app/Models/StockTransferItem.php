<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:3',
            'quantity_shipped' => 'decimal:3',
            'quantity_received' => 'decimal:3',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Écart constaté à la réception (quantité reçue − quantité expédiée).
     */
    public function discrepancy(): float
    {
        if ($this->quantity_received === null || $this->quantity_shipped === null) {
            return 0.0;
        }

        return round((float) $this->quantity_received - (float) $this->quantity_shipped, 3);
    }
}
