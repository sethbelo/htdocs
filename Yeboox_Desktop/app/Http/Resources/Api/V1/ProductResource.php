<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,
            'unit' => $this->whenLoaded('unitOfMeasure', fn () => $this->unitOfMeasure ? [
                'id' => $this->unitOfMeasure->id,
                'name' => $this->unitOfMeasure->name,
                'symbol' => $this->unitOfMeasure->symbol,
            ] : null),
            'price' => (float) $this->price,
            'net_price' => $this->netUnitPrice(),
            'gross_price' => $this->grossUnitPrice(),
            'tax_rate' => (float) $this->sales_tax_rate,
            'tax_included' => (bool) $this->tax_included,
            'stock_quantity' => (float) $this->stock_qty,
            'alert_quantity' => (float) $this->alert_qty,
            'is_active' => (bool) $this->is_active,
            'image_url' => $this->image_path ? asset('storage/'.ltrim($this->image_path, '/')) : null,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
