<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'description',
        'image_path',
        'category',
        'unit_of_measure_id',
        'type',
        'price',
        'sales_tax_rate',
        'tax_included',
        'cost',
        'stock_qty',
        'alert_qty',
        'is_active',
        'internal_notes',
    ];

    protected $casts = [
        'stock_qty' => 'decimal:3',
        'alert_qty' => 'decimal:3',
        'price' => 'decimal:8',
        'sales_tax_rate' => 'decimal:2',
        'tax_included' => 'boolean',
        'cost' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ProductComboItem::class, 'combo_product_id');
    }

    public function componentOfCombos(): HasMany
    {
        return $this->hasMany(ProductComboItem::class, 'component_product_id');
    }

    /** Prix hors taxe utilisé pour la comptabilité et les lignes de vente. */
    public function netUnitPrice(): float
    {
        $price = (float) $this->price;
        $rate = max(0, (float) $this->sales_tax_rate);

        if (! $this->tax_included || $rate <= 0) {
            return round($price, 8);
        }

        return round($price / (1 + ($rate / 100)), 2);
    }

    /** Prix toutes taxes comprises présenté au client. */
    public function grossUnitPrice(): float
    {
        $price = (float) $this->price;
        $rate = max(0, (float) $this->sales_tax_rate);

        if ($this->tax_included || $rate <= 0) {
            return round($price, 8);
        }

        return round($price * (1 + ($rate / 100)), 8);
    }

    /** Représentation du prix dans le mode fiscal demandé. */
    public function priceForTaxMode(bool $taxIncluded): float
    {
        return $taxIncluded ? $this->grossUnitPrice() : $this->netUnitPrice();
    }
}
