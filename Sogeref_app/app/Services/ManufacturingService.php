<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Voucher;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Atelier : génération des ordres de fabrication / dossiers de construction,
 * explosion des nomenclatures, bons de sortie matière et clôture.
 */
class ManufacturingService
{
    public const DEFAULT_OPERATIONS = [
        'fabrication' => [['Débit & découpe', 'Scie / découpe', 4], ['Usinage & assemblage', 'Établi', 6], ['Ponçage & préparation', 'Ponceuse', 3], ['Finition (vernis / peinture)', 'Cabine finition', 4], ['Garnissage & montage final', 'Montage', 3], ['Contrôle qualité', 'Qualité', 1]],
        'construction' => [['Implantation & terrassement', 'Chantier', 16], ['Fondations', 'Chantier', 40], ['Élévation des murs', 'Chantier', 80], ['Charpente & toiture', 'Chantier', 40], ['Réseaux (plomberie, électricité)', 'Chantier', 32], ['Finitions & peinture', 'Chantier', 48], ['Réception des travaux', 'Qualité', 4]],
    ];

    public function __construct(private readonly SequenceService $sequences, private readonly VoucherService $vouchers, private readonly StockService $stock) {}

    public function createFromOrder(SalesOrder $order, array $options = []): ManufacturingOrder
    {
        $type = $options['type'] ?? ($order->kind === 'construction' ? 'construction' : 'fabrication');

        return DB::transaction(function () use ($order, $options, $type) {
            $items = $order->quote?->items ?? collect();
            $mainProduct = $items->first(fn ($i) => $i->product?->is_manufactured)?->product;

            $mo = ManufacturingOrder::create([
                'company_id' => $order->company_id,
                'sales_order_id' => $order->id,
                'product_id' => $type === 'fabrication' ? $mainProduct?->id : null,
                'warehouse_id' => $options['warehouse_id'] ?? Warehouse::where('company_id', $order->company_id)->where('type', $type === 'construction' ? 'central' : 'atelier')->value('id'),
                'responsible_id' => $options['responsible_id'] ?? null,
                'number' => $this->sequences->next('manufacturing_order', $order->company_id),
                'type' => $type,
                'title' => $order->title,
                'description' => $order->description,
                'specifications' => $options['specifications'] ?? $this->specificationsFrom($order),
                'quantity' => $type === 'fabrication' ? max(1, (float) $items->filter(fn ($i) => $i->product?->is_manufactured)->sum('quantity')) : 1,
                'status' => 'confirmed',
                'priority' => $order->priority,
                'planned_start' => now()->toDateString(),
                'planned_end' => $order->delivery_date ?? now()->addDays($type === 'construction' ? 60 : 14)->toDateString(),
            ]);

            $this->explodeMaterials($mo, $items);
            foreach (self::DEFAULT_OPERATIONS[$type] as $position => [$name, $station, $hours]) {
                $mo->operations()->create(['name' => $name, 'workstation' => $station, 'planned_hours' => $hours, 'position' => $position]);
            }

            return $mo->load('materials', 'operations');
        });
    }

    /** Ordre de fabrication pour le stock (sans commande client). */
    public function createForStock(Product $product, float $quantity, array $options = []): ManufacturingOrder
    {
        return DB::transaction(function () use ($product, $quantity, $options) {
            $mo = ManufacturingOrder::create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'warehouse_id' => $options['warehouse_id'] ?? Warehouse::where('company_id', $product->company_id)->where('type', 'atelier')->value('id'),
                'responsible_id' => $options['responsible_id'] ?? null,
                'number' => $this->sequences->next('manufacturing_order', $product->company_id),
                'type' => 'fabrication',
                'title' => $options['title'] ?? $product->name,
                'description' => $options['description'] ?? $product->description,
                'specifications' => $options['specifications'] ?? [],
                'quantity' => $quantity,
                'status' => 'confirmed',
                'priority' => $options['priority'] ?? 'normale',
                'planned_start' => $options['planned_start'] ?? now()->toDateString(),
                'planned_end' => $options['planned_end'] ?? now()->addDays(10)->toDateString(),
            ]);
            $this->explodeMaterials($mo, collect([(object) ['product' => $product, 'quantity' => $quantity]]));
            foreach (self::DEFAULT_OPERATIONS['fabrication'] as $position => [$name, $station, $hours]) {
                $mo->operations()->create(['name' => $name, 'workstation' => $station, 'planned_hours' => $hours, 'position' => $position]);
            }

            return $mo;
        });
    }

    /** Liste de matériel : nomenclatures des produits fabriqués + matières vendues en direct. */
    public function explodeMaterials(ManufacturingOrder $mo, $items): void
    {
        $needs = [];
        foreach ($items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }
            $bom = Bom::with('lines.product')->where('product_id', $product->id)->first();
            if ($bom) {
                $ratio = (float) $item->quantity / max(0.0001, (float) $bom->yield_quantity);
                foreach ($bom->lines as $line) {
                    $needs[$line->product_id] = ($needs[$line->product_id] ?? 0) + (float) $line->quantity * $ratio;
                }
            } elseif (in_array($product->type, ['material', 'consumable'], true)) {
                $needs[$product->id] = ($needs[$product->id] ?? 0) + (float) $item->quantity;
            }
        }

        foreach (Product::whereIn('id', array_keys($needs))->get() as $product) {
            $mo->materials()->create([
                'product_id' => $product->id,
                'description' => $product->name,
                'unit' => $product->unit,
                'quantity_planned' => round($needs[$product->id], 4),
                'unit_cost' => $product->purchase_price,
            ]);
        }
    }

    private function specificationsFrom(SalesOrder $order): array
    {
        return collect($order->quote?->items ?? [])->map(fn ($item) => [
            'label' => $item->description,
            'value' => qty($item->quantity).' '.$item->unit,
        ])->values()->all();
    }

    // ── Cycle de vie ─────────────────────────────────────────────────────
    public function start(ManufacturingOrder $mo): void
    {
        if (! in_array($mo->status, ['draft', 'confirmed'], true)) {
            throw new DomainException('Cet ordre est déjà lancé.');
        }
        $mo->update(['status' => 'in_progress', 'started_at' => now(), 'progress' => max(5, $mo->progress)]);
    }

    public function toQuality(ManufacturingOrder $mo): void
    {
        if ($mo->status !== 'in_progress') {
            throw new DomainException('Seul un ordre en production peut passer au contrôle qualité.');
        }
        $mo->update(['status' => 'quality', 'progress' => max(90, $mo->progress)]);
    }

    public function finish(ManufacturingOrder $mo): void
    {
        if (! in_array($mo->status, ['in_progress', 'quality'], true)) {
            throw new DomainException('L’ordre doit être en production ou en contrôle qualité.');
        }

        DB::transaction(function () use ($mo) {
            $mo->operations()->where('status', '!=', 'done')->update(['status' => 'done']);
            $mo->update(['status' => 'done', 'finished_at' => now(), 'progress' => 100]);

            // Entrée en stock du produit fini (showroom ou atelier).
            if ($mo->type === 'fabrication' && $mo->product && $mo->product->track_stock && $mo->warehouse) {
                $this->stock->receive($mo->product, $mo->warehouse, (float) $mo->quantity, 'produce', $mo, $mo->number, $mo->materialCost() / max(1, (float) $mo->quantity));
            }
            $mo->salesOrder?->events()->create(['user_id' => auth()->id(), 'from_stage' => $mo->salesOrder->stage, 'to_stage' => $mo->salesOrder->stage, 'title' => "Fabrication {$mo->number} terminée — prête à livrer"]);
        });
    }

    /** Génère un bon de sortie matière pour les quantités restant à consommer. */
    public function requestMaterials(ManufacturingOrder $mo): Voucher
    {
        $lines = $mo->materials->filter(fn ($m) => $m->product_id && (float) $m->quantity_planned > (float) $m->quantity_consumed)
            ->map(fn ($m) => ['product_id' => $m->product_id, 'description' => $m->description, 'quantity' => round((float) $m->quantity_planned - (float) $m->quantity_consumed, 4), 'unit' => $m->unit, 'unit_price' => (float) $m->unit_cost])
            ->values()->all();

        if (! $lines) {
            throw new DomainException('Toutes les matières ont déjà été sorties du magasin.');
        }

        return $this->vouchers->create('sortie', [
            'company_id' => $mo->company_id,
            'voucher_date' => now()->toDateString(),
            'warehouse_id' => Warehouse::where('company_id', $mo->company_id)->where('type', 'central')->value('id') ?? $mo->warehouse_id,
            'manufacturing_order_id' => $mo->id,
            'sales_order_id' => $mo->sales_order_id,
            'site_id' => $mo->salesOrder?->site_id,
            'beneficiary' => $mo->type === 'construction' ? 'Chantier '.$mo->title : 'Atelier de fabrication',
            'purpose' => "Matières pour {$mo->number} — {$mo->title}",
        ], $lines, submit: true);
    }
}
