<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Traits\Auditable;
use App\Traits\HasEstablishment;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use Auditable, HasEstablishment, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'establishment_id', 'category_id', 'name', 'slug', 'sku', 'barcode',
        'rfid_tag', 'description', 'image_path', 'icon', 'type',
        'selling_price', 'cost_price', 'promo_price', 'promo_start', 'promo_end',
        'tax_id', 'unit_id', 'alert_quantity', 'track_stock',
        'kitchen_station_id', 'printer_id',
        'is_available', 'is_active', 'sort_order', 'loyalty_points',
        'created_by',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'selling_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'promo_price' => 'decimal:4',
        'alert_quantity' => 'decimal:4',
        'promo_start' => 'datetime',
        'promo_end' => 'datetime',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'sort_order' => 'integer',
        'loyalty_points' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = strtoupper(Str::random(8));
            }
        });
    }

    // ── Relationships ──────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function components()
    {
        return $this->hasMany(ProductComponent::class);
    }

    public function componentProducts()
    {
        return $this->belongsToMany(Product::class, 'product_components', 'product_id', 'component_product_id')
            ->withPivot(['id', 'unit_id', 'quantity'])
            ->withTimestamps();
    }

    public function addons()
    {
        return $this->belongsToMany(ProductAddon::class, 'product_addon_product');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', $search)
                ->orWhere('rfid_tag', $search);
        });
    }

    public function scopeByCategory($query, ?string $categoryId)
    {
        if (! $categoryId) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Accessors ──────────────────────────────

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    /**
     * Get the effective selling price (promo if active, otherwise regular).
     */
    public function getEffectivePriceAttribute(): float
    {
        if ($this->promo_price && $this->isPromoActive()) {
            return (float) $this->promo_price;
        }

        return (float) $this->selling_price;
    }

    /**
     * Gross margin percentage.
     */
    public function getMarginAttribute(): float
    {
        if ($this->selling_price <= 0) {
            return 0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->selling_price) * 100, 2);
    }

    // ── Helpers ──────────────────────────────

    public function isPromoActive(): bool
    {
        $now = now();

        return $this->promo_price
            && (! $this->promo_start || $now->gte($this->promo_start))
            && (! $this->promo_end || $now->lte($this->promo_end));
    }

    public function isComposite(): bool
    {
        return $this->type === ProductType::Composite;
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }
}
