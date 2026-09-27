<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMove extends Model
{
    use BelongsToCompany;

    public const TYPES = [
        'in' => ['Entrée', 'success'], 'out' => ['Sortie', 'danger'], 'transfer' => ['Transfert', 'info'], 'adjust' => ['Ajustement', 'neutral'],
        'consume' => ['Consommation atelier', 'warning'], 'produce' => ['Production', 'accent'], 'sale' => ['Vente', 'brand'], 'purchase' => ['Réception achat', 'success'],
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4', 'unit_cost' => 'decimal:2', 'moved_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
