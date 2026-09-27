<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

class StockLevelChanged
{
    use Dispatchable;

    public function __construct(public Product $product, public int $warehouseId, public float $balance) {}
}
