<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'type' => $this->type->value,
            'sale_price' => (float) $this->sale_price,
            'tax_rate' => (float) $this->tax_rate,
            'is_sellable_pos' => $this->is_sellable_pos,
            'stock' => $this->whenLoaded('stockLevels', fn () => $this->stockLevels->map(fn ($level): array => ['warehouse_id' => $level->warehouse_id, 'quantity' => (float) $level->quantity])),
        ];
    }
}
