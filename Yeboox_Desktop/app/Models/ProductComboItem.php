<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComboItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'combo_product_id',
        'component_product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
    ];

    public function comboProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
