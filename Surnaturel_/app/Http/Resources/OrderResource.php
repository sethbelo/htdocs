<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => ['code' => $this->status->value, 'label' => $this->status->label()],
            'priority' => $this->priority->value,
            'client' => ClientResource::make($this->whenLoaded('client')),
            'site_id' => $this->site_id,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'requires_design' => $this->requires_design,
            'totals' => [
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount_total,
                'tax' => (float) $this->tax_total,
                'total' => (float) $this->total,
                'deposit_required' => (float) $this->deposit_required,
                'paid' => (float) $this->amount_paid,
                'balance' => $this->balanceDue(),
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'specifications' => $item->specSummary(),
            ])),
        ];
    }
}
