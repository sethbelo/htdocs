<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_reference' => $this->client_reference,
            'source' => $this->source,
            'sync_uuid' => $this->sync_uuid,
            'sync_status' => $this->sync_status,
            'synced_at' => $this->synced_at?->toISOString(),
            'department' => $this->department,
            'fulfillment_type' => $this->fulfillment_type,
            'table_name' => $this->table_name,
            'delivery_phone' => $this->delivery_phone,
            'delivery_address' => $this->delivery_address,
            'total_amount' => (float) $this->total_amount,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'paid_amount' => (float) $this->paid_amount,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? ['id' => $this->customer->id, 'name' => $this->customer->name] : null),
            'cashier' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'sku' => $item->product?->sku,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->sales_tax_rate,
                'tax_amount' => (float) $item->tax_amount,
                'total_price' => (float) $item->total_price,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'method' => $payment->method,
                'provider' => $payment->provider,
                'reference' => $payment->reference,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'metadata' => $payment->metadata,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
