<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, TracksActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'max_stock' => 'decimal:3',
            'is_stockable' => 'boolean',
            'is_sellable_pos' => 'boolean',
            'is_favorite' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockIn(Warehouse|int $warehouse): float
    {
        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->id : $warehouse;

        return (float) $this->stockLevels()->where('warehouse_id', $warehouseId)->value('quantity');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(fn (Builder $w) => $w
            ->where('name', 'like', "%{$term}%")
            ->orWhere('sku', $term)
            ->orWhere('barcode', $term)
            ->orWhere('rfid_tag', $term)));
    }
}
