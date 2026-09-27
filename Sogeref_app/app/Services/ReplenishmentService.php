<?php

namespace App\Services;

use App\Actions\Documents\CreateDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Product;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Achats : réapprovisionnement automatique et réception des commandes.
 *
 * Règle : dès que le stock total ≤ seuil de réappro, on propose
 *         max(qté de réappro, 2 × seuil − stock) auprès du fournisseur
 *         par défaut, en tenant compte des commandes déjà en cours.
 */
class ReplenishmentService
{
    public function __construct(private readonly CreateDocument $documents, private readonly StockService $stock) {}

    public function proposals(?int $companyId = null): Collection
    {
        $companyId ??= company_id();
        $incoming = $this->incomingQuantities($companyId);

        return Product::with('supplier')
            ->where('company_id', $companyId)
            ->where('track_stock', true)
            ->where('is_purchasable', true)
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($incoming) {
                $pending = (float) ($incoming[$product->id] ?? 0);
                $target = max((float) $product->reorder_quantity, 2 * (float) $product->reorder_level - (float) $product->stock_quantity);
                $product->incoming = $pending;
                $product->suggested = max(0, round($target - $pending, 2));

                return $product;
            })
            ->filter(fn ($p) => $p->suggested > 0)
            ->values();
    }

    /** Crée un bon de commande fournisseur par fournisseur. */
    public function generate(?array $productIds = null, ?int $companyId = null): Collection
    {
        $companyId ??= company_id();
        $proposals = $this->proposals($companyId)->when($productIds, fn ($c) => $c->whereIn('id', $productIds));
        if ($proposals->isEmpty()) {
            throw new DomainException('Aucun article sous le seuil de réapprovisionnement.');
        }

        return DB::transaction(function () use ($proposals, $companyId) {
            return $proposals->groupBy(fn ($p) => $p->default_supplier_id ?: 0)->map(function ($products, $supplierId) use ($companyId) {
                $supplierId = $supplierId ?: Contact::where('company_id', $companyId)->where('type', 'supplier')->value('id');
                if (! $supplierId) {
                    throw new DomainException('Créez au moins un fournisseur pour générer les commandes.');
                }

                return $this->documents->handle([
                    'company_id' => $companyId,
                    'contact_id' => $supplierId,
                    'created_by' => auth()->id(),
                    'type' => DocumentType::PurchaseOrder,
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addDays((int) $products->max('lead_time_days') ?: 7)->toDateString(),
                    'currency_code' => 'USD',
                    'exchange_rate' => 1,
                    'notes' => 'Commande générée automatiquement par le réapprovisionnement.',
                    'meta' => ['auto_replenishment' => true],
                ], $products->values()->map(fn ($p, $i) => [
                    'product_id' => $p->id, 'description' => $p->name, 'quantity' => $p->suggested,
                    'unit' => $p->unit, 'unit_price' => (float) $p->purchase_price, 'tax_rate' => 0, 'position' => $i,
                ])->all());
            })->values();
        });
    }

    /** Réception (totale ou partielle) d'un bon de commande fournisseur. */
    public function receive(Document $order, Warehouse $warehouse, array $quantities): void
    {
        if ($order->type !== DocumentType::PurchaseOrder) {
            throw new DomainException('Seul un bon de commande fournisseur peut être réceptionné.');
        }

        DB::transaction(function () use ($order, $warehouse, $quantities) {
            $received = 0;
            foreach ($order->items as $item) {
                $qty = round((float) ($quantities[$item->id] ?? 0), 4);
                $remaining = (float) $item->quantity - (float) $item->received_quantity;
                if ($qty <= 0) {
                    continue;
                }
                if ($qty > $remaining + 1e-6) {
                    throw new DomainException("Quantité reçue supérieure au reliquat pour « {$item->description} ».");
                }
                if ($item->product && $item->product->track_stock) {
                    $this->stock->receive($item->product, $warehouse, $qty, 'purchase', $order, $order->number, (float) $item->unit_price);
                    $item->product->update(['purchase_price' => $item->unit_price]);
                }
                $item->update(['received_quantity' => (float) $item->received_quantity + $qty]);
                $received++;
            }
            if (! $received) {
                throw new DomainException('Saisissez au moins une quantité reçue.');
            }

            $complete = $order->items()->whereColumn('received_quantity', '<', 'quantity')->doesntExist();
            $meta = $order->meta ?? [];
            $meta['reception'] = $complete ? 'complete' : 'partial';
            $meta['received_at'] = now()->toDateTimeString();
            $order->update(['meta' => $meta, 'status' => $complete ? DocumentStatus::Accepted : DocumentStatus::Sent]);
        });
    }

    private function incomingQuantities(int $companyId): array
    {
        return DB::table('document_items')
            ->join('documents', 'documents.id', '=', 'document_items.document_id')
            ->where('documents.company_id', $companyId)
            ->where('documents.type', DocumentType::PurchaseOrder->value)
            ->whereNull('documents.deleted_at')
            ->whereNotIn('documents.status', ['cancelled', 'rejected'])
            ->whereColumn('document_items.received_quantity', '<', 'document_items.quantity')
            ->groupBy('document_items.product_id')
            ->selectRaw('document_items.product_id, SUM(document_items.quantity - document_items.received_quantity) as qty')
            ->pluck('qty', 'product_id')
            ->all();
    }
}
