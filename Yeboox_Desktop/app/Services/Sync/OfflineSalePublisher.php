<?php

namespace App\Services\Sync;

use App\Models\Sale;
use App\Models\SyncOutbox;
use Illuminate\Support\Str;

/** Enregistre la vente dans l'outbox locale ; aucun accès réseau n'a lieu à la caisse. */
final class OfflineSalePublisher
{
    public function __construct(private readonly SaleSyncPayloadFactory $payloadFactory) {}

    public function publish(Sale $sale): ?SyncOutbox
    {
        if (! config('offline-sync.enabled') || config('offline-sync.runtime') !== 'desktop') {
            return null;
        }

        if (! $sale->sync_uuid) {
            $sale->forceFill(['sync_uuid' => (string) Str::uuid()])->saveQuietly();
        }

        if ($sale->registerSession && ! $sale->registerSession->sync_uuid) {
            $sale->registerSession->forceFill(['sync_uuid' => (string) Str::uuid()])->saveQuietly();
        }

        $sale->forceFill(['sync_status' => 'pending', 'synced_at' => null])->saveQuietly();

        return SyncOutbox::query()->updateOrCreate(
            ['event_uuid' => $sale->sync_uuid],
            [
                'company_id' => $sale->company_id,
                'establishment_id' => $sale->establishment_id,
                'point_of_sale_id' => $sale->point_of_sale_id,
                'aggregate_type' => 'sale',
                'aggregate_uuid' => $sale->sync_uuid,
                'event_type' => 'sale.completed',
                'payload' => $this->payloadFactory->make($sale->fresh()),
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
                'last_error' => null,
            ],
        );
    }
}
