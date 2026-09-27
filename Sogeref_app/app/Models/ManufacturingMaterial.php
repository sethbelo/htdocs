<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturingMaterial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity_planned' => 'decimal:4', 'quantity_consumed' => 'decimal:4', 'unit_cost' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
