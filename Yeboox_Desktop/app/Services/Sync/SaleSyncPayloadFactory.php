<?php

namespace App\Services\Sync;

use App\Models\Sale;
use App\Support\DisplayCurrency;

/** Construit un instantané autonome de la vente, sans dépendre des futurs IDs centraux. */
final class SaleSyncPayloadFactory
{
    public function make(Sale $sale): array
    {
        $sale->loadMissing([
            'company', 'establishment', 'pointOfSale', 'customer', 'user',
            'registerSession', 'items.product.unitOfMeasure', 'payments.paymentMethod',
            'accountingRecords',
        ]);

        return [
            'schema_version' => 1,
            'company_uuid' => $sale->company?->uuid,
            'point_of_sale_uuid' => $sale->pointOfSale?->uuid,
            'occurred_at' => $sale->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'sale' => [
                'sync_uuid' => $sale->sync_uuid,
                'client_reference' => $sale->client_reference,
                'source' => $sale->source ?: 'desktop',
                'department' => $sale->department,
                'fulfillment_type' => $sale->fulfillment_type,
                'table_name' => $sale->table_name,
                'delivery_address' => $sale->delivery_address,
                'delivery_phone' => $sale->delivery_phone,
                'payment_method' => $sale->payment_method,
                'payment_reference' => $sale->payment_reference,
                'total_amount' => (float) $sale->total_amount,
                'discount_amount' => (float) $sale->discount_amount,
                'tax_amount' => (float) $sale->tax_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'points_earned' => (int) $sale->points_earned,
                'points_redeemed' => (int) $sale->points_redeemed,
            ],
            'user' => $sale->user ? [
                'name' => $sale->user->name,
                'email' => $sale->user->email,
            ] : null,
            'customer' => $sale->customer ? [
                'name' => $sale->customer->name,
                'phone' => $sale->customer->phone,
                'email' => $sale->customer->email,
            ] : null,
            'register_session' => $sale->registerSession ? [
                'sync_uuid' => $sale->registerSession->sync_uuid,
                'opened_at' => $sale->registerSession->opened_at?->toIso8601String(),
                'closed_at' => $sale->registerSession->closed_at?->toIso8601String(),
                'opening_balance' => (float) $sale->registerSession->opening_balance,
                'base_currency' => $sale->registerSession->base_currency ?: DisplayCurrency::baseCode(),
                'secondary_currency' => $sale->registerSession->secondary_currency ?: DisplayCurrency::secondaryCode(),
                'base_to_secondary_rate' => (float) ($sale->registerSession->base_to_secondary_rate ?: DisplayCurrency::rate()),
                'status' => $sale->registerSession->status,
            ] : null,
            'items' => $sale->items->map(fn ($item) => [
                'product' => [
                    'sku' => $item->product?->sku,
                    'name' => $item->product?->name ?? 'Article supprimé',
                    'category' => $item->product?->category ?: 'Général',
                    'type' => $item->product?->type ?: 'product',
                    'unit' => $item->product?->unitOfMeasure?->symbol,
                    'cost' => (float) ($item->product?->cost ?? 0),
                    'tax_included' => (bool) ($item->product?->tax_included ?? false),
                ],
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'sales_tax_rate' => (float) $item->sales_tax_rate,
                'total_price' => (float) $item->total_price,
                'tax_amount' => (float) $item->tax_amount,
            ])->values()->all(),
            'payments' => $sale->payments->map(fn ($payment) => [
                'method' => $payment->method,
                'provider' => $payment->provider,
                'reference' => $payment->reference,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'points_redeemed' => (int) $payment->points_redeemed,
                'metadata' => $payment->metadata,
            ])->values()->all(),
            'accounting_records' => $sale->accountingRecords->map(fn ($record) => [
                'reference' => $record->reference,
                'journal_code' => $record->journal_code,
                'entry_type' => $record->entry_type,
                'record_date' => $record->record_date?->format('Y-m-d'),
                'syscohada_code' => $record->syscohada_code,
                'debit' => (float) $record->debit,
                'credit' => (float) $record->credit,
                'description' => $record->description,
            ])->values()->all(),
        ];
    }
}
