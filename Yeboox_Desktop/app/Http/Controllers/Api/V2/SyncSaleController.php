<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Sync\CentralSaleIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncSaleController extends Controller
{
    public function __invoke(Request $request, CentralSaleIngestor $ingestor): JsonResponse
    {
        $data = $request->validate([
            'event_uuid' => ['required', 'uuid'],
            'event_type' => ['required', 'in:sale.completed'],
            'payload' => ['required', 'array'],
            'payload.schema_version' => ['required', 'integer', 'in:1'],
            'payload.company_uuid' => ['required', 'uuid'],
            'payload.point_of_sale_uuid' => ['required', 'uuid'],
            'payload.occurred_at' => ['required', 'date'],
            'payload.sale' => ['required', 'array'],
            'payload.sale.sync_uuid' => ['required', 'uuid'],
            'payload.sale.total_amount' => ['required', 'numeric', 'min:0'],
            'payload.sale.paid_amount' => ['required', 'numeric', 'min:0'],
            // Certaines ventes historiques importées avant la gestion des
            // lignes ne contiennent aucun article, mais restent comptables.
            'payload.items' => ['present', 'array'],
            'payload.items.*.product' => ['required', 'array'],
            'payload.items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'payload.items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'payload.items.*.total_price' => ['required', 'numeric', 'min:0'],
            'payload.payments' => ['sometimes', 'array'],
            'payload.accounting_records' => ['sometimes', 'array'],
        ]);

        $result = $ingestor->ingest(
            $data['event_uuid'],
            $data['payload'],
            (string) $request->attributes->get('sync_token_hash'),
        );

        return response()->json(['message' => 'Vente synchronisée.', 'data' => $result]);
    }
}
