<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2', 'purchase_price' => 'decimal:2', 'tax_rate' => 'decimal:4',
            'stock_quantity' => 'decimal:4', 'reorder_level' => 'decimal:4', 'reorder_quantity' => 'decimal:4',
            'track_stock' => 'boolean', 'is_active' => 'boolean', 'is_sellable' => 'boolean',
            'is_purchasable' => 'boolean', 'is_manufactured' => 'boolean', 'pos_visible' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'default_supplier_id')->withTrashed();
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }

    public function bom(): HasOne
    {
        return $this->hasOne(Bom::class);
    }

    public function typeLabel(): string
    {
        return config("erp.product_types.{$this->type}", ucfirst($this->type));
    }

    public function isLow(): bool
    {
        return $this->track_stock && (float) $this->reorder_level > 0 && (float) $this->stock_quantity <= (float) $this->reorder_level;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
