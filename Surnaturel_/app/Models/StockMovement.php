<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'moved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function relatedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'related_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Personne ayant physiquement effectué l'opération (livreur, retirant, convoyeur…).
     */
    public function performerName(): ?string
    {
        return match (true) {
            $this->source instanceof StockOperation => $this->source->performed_by_name,
            $this->source instanceof StockTransfer => $this->source->carrier_name,
            $this->source instanceof Sale => $this->source->cashier?->name,
            $this->source instanceof PrintJob => $this->source->operator?->name,
            default => null,
        };
    }
}
