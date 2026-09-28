<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rapports d'entrepôt lisibles : chaque rapport renvoie un titre, une explication, des indicateurs
 * et un tableau (en-têtes, lignes, lignes de total, colonnes numériques) prêt pour l'écran, le PDF et Excel.
 */
class InventoryReports
{
    /**
     * @var array<string, array{label: string, icon: string, description: string}>
     */
    public const REPORTS = [
        'valuation' => ['label' => 'État des stocks valorisé', 'icon' => 'banknotes', 'description' => 'Quantité, coût unitaire et valeur de chaque article à la date de fin, avec son statut par rapport au seuil minimum.'],
        'movements' => ['label' => 'Fiche des mouvements', 'icon' => 'arrows-up-down', 'description' => 'Pour chaque article : stock d’ouverture, entrées, sorties et stock de clôture sur la période.'],
        'alerts' => ['label' => 'Ruptures & réapprovisionnement', 'icon' => 'exclamation-triangle', 'description' => 'Articles en rupture ou sous le seuil minimum, avec la quantité conseillée à commander.'],
        'by_type' => ['label' => 'Entrées & sorties par nature', 'icon' => 'chart-pie', 'description' => 'Volumes et valeurs par nature de mouvement : achats, ventes, consommation atelier, pertes, transferts…'],
        'dormant' => ['label' => 'Articles dormants', 'icon' => 'moon', 'description' => 'Articles en stock sans aucun mouvement depuis 60 jours : capital immobilisé à écouler.'],
        'people' => ['label' => 'Activité du personnel', 'icon' => 'user-group', 'description' => 'Mouvements enregistrés par chaque magasinier ou caissier, et personnes ayant effectué les livraisons et retraits.'],
        'transfers' => ['label' => 'Transferts inter-entrepôts', 'icon' => 'arrows-right-left', 'description' => 'Transferts de la période, statut, expéditeur, réceptionnaire et écarts constatés.'],
    ];

    /**
     * @return array{title: string, description: string, kpis: list<array{label: string, value: string, tone?: string}>, headings: list<string>, rows: list<list<mixed>>, totals: list<int>, statuses: array<int, string>}
     */
    public function build(string $report, User $user, ?int $warehouseId, string $from, string $to, ?int $categoryId = null): array
    {
        $warehouses = Warehouse::query()->visibleTo($user)->when($warehouseId, fn (Builder $q) => $q->whereKey($warehouseId))->pluck('id');
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $data = match ($report) {
            'movements' => $this->movements($warehouses, $start, $end, $categoryId),
            'alerts' => $this->alerts($warehouses, $categoryId),
            'by_type' => $this->byType($warehouses, $start, $end),
            'dormant' => $this->dormant($warehouses, $categoryId),
            'people' => $this->people($warehouses, $start, $end),
            'transfers' => $this->transfers($warehouses, $start, $end),
            default => $this->valuation($warehouses, $end, $categoryId),
        };

        return ['title' => self::REPORTS[$report]['label'] ?? self::REPORTS['valuation']['label'], 'description' => self::REPORTS[$report]['description'] ?? '', 'statuses' => []] + $data;
    }

    /**
     * Expression SQL de la quantité signée (+ entrée / − sortie) d'un mouvement.
     */
    private function signedSql(): string
    {
        $in = collect(StockMovementType::cases())->filter(fn ($t) => $t->direction() === 1)->map(fn ($t) => "'{$t->value}'")->implode(',');
        $out = collect(StockMovementType::cases())->filter(fn ($t) => $t->direction() === -1)->map(fn ($t) => "'{$t->value}'")->implode(',');

        return "CASE WHEN stock_movements.type IN ({$in}) THEN ABS(stock_movements.quantity) WHEN stock_movements.type IN ({$out}) THEN -ABS(stock_movements.quantity) ELSE stock_movements.quantity END";
    }

    /**
     * Stock par article × entrepôt à une date : stock actuel − mouvements postérieurs.
     *
     * @param  Collection<int, int>  $warehouses
     * @return Collection<string, object{product_id: int, warehouse_id: int, quantity: float}>
     */
    private function stockAt(Collection $warehouses, Carbon $at, ?int $categoryId): Collection
    {
        $after = StockMovement::query()->whereIn('warehouse_id', $warehouses)->where('moved_at', '>', $at)
            ->groupBy('product_id', 'warehouse_id')
            ->selectRaw('product_id, warehouse_id, SUM('.$this->signedSql().') as delta')
            ->toBase()->get()->keyBy(fn ($r) => $r->product_id.'-'.$r->warehouse_id);

        return StockLevel::query()->whereIn('warehouse_id', $warehouses)
            ->when($categoryId, fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('product_category_id', $categoryId)))
            ->get(['product_id', 'warehouse_id', 'quantity'])
            ->mapWithKeys(fn (StockLevel $level) => [$level->product_id.'-'.$level->warehouse_id => (object) [
                'product_id' => $level->product_id,
                'warehouse_id' => $level->warehouse_id,
                'quantity' => round((float) $level->quantity - (float) ($after[$level->product_id.'-'.$level->warehouse_id]->delta ?? 0), 3),
            ]]);
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function valuation(Collection $warehouses, Carbon $end, ?int $categoryId): array
    {
        $stock = $this->stockAt($warehouses, $end, $categoryId);
        $products = Product::query()->with('unit', 'category')->whereIn('id', $stock->pluck('product_id'))->get()->keyBy('id');
        $names = Warehouse::query()->whereIn('id', $warehouses)->pluck('name', 'id');

        $rows = $stock->map(function ($s) use ($products, $names): array {
            $product = $products[$s->product_id];
            $status = match (true) {
                $s->quantity <= 0 => 'Rupture',
                (float) $product->min_stock > 0 && $s->quantity <= (float) $product->min_stock => 'Sous le seuil',
                $product->max_stock && $s->quantity > (float) $product->max_stock => 'Surstock',
                default => 'Normal',
            };

            return [$product->sku, $product->name, $product->category?->name ?? '-', $names[$s->warehouse_id] ?? '-', (float) $s->quantity, $product->unit?->symbol ?? '', (float) $product->min_stock, (float) $product->cost_price, round(max(0, $s->quantity) * (float) $product->cost_price, 2), $status];
        });

        $order = ['Rupture' => 0, 'Sous le seuil' => 1, 'Surstock' => 2, 'Normal' => 3];
        $rows = $rows->sortBy(fn ($r) => sprintf('%d-%s', $order[$r[9]], $r[1]))->values();
        $total = (float) $rows->sum(8);
        $rows->push(['', 'VALEUR TOTALE DU STOCK', '', '', null, '', null, null, round($total, 2), '']);

        return [
            'kpis' => [
                ['label' => 'Valeur du stock', 'value' => Money::format($total)],
                ['label' => 'Références', 'value' => (string) $stock->pluck('product_id')->unique()->count()],
                ['label' => 'En rupture', 'value' => (string) $rows->where(9, 'Rupture')->count(), 'tone' => 'red'],
                ['label' => 'Sous le seuil', 'value' => (string) $rows->where(9, 'Sous le seuil')->count(), 'tone' => 'amber'],
            ],
            'headings' => ['Réf.', 'Article', 'Catégorie', 'Entrepôt', 'Quantité', 'Unité', 'Seuil min.', 'Coût unitaire', 'Valeur', 'Statut'],
            'rows' => $rows->all(),
            'totals' => [$rows->count() - 1],
            'statusColumn' => 9,
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function movements(Collection $warehouses, Carbon $start, Carbon $end, ?int $categoryId): array
    {
        $closing = $this->stockAt($warehouses, $end, $categoryId);
        $period = StockMovement::query()->whereIn('warehouse_id', $warehouses)->whereBetween('moved_at', [$start, $end])
            ->when($categoryId, fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('product_category_id', $categoryId)))
            ->groupBy('product_id', 'warehouse_id')
            ->selectRaw('product_id, warehouse_id, SUM(CASE WHEN ('.$this->signedSql().') > 0 THEN ('.$this->signedSql().') ELSE 0 END) as qty_in, SUM(CASE WHEN ('.$this->signedSql().') < 0 THEN -('.$this->signedSql().') ELSE 0 END) as qty_out, COUNT(*) as moves')
            ->toBase()->get()->keyBy(fn ($r) => $r->product_id.'-'.$r->warehouse_id);

        $products = Product::query()->with('unit')->whereIn('id', $period->pluck('product_id'))->get()->keyBy('id');
        $names = Warehouse::query()->whereIn('id', $warehouses)->pluck('name', 'id');

        $rows = $period->map(function ($p, string $key) use ($closing, $products, $names): array {
            $product = $products[$p->product_id];
            $close = (float) ($closing[$key]->quantity ?? 0);
            $in = round((float) $p->qty_in, 3);
            $out = round((float) $p->qty_out, 3);

            return [$product->sku, $product->name, $names[$p->warehouse_id] ?? '-', round($close - $in + $out, 3), $in, $out, $close, $product->unit?->symbol ?? '', (int) $p->moves, round(($in - $out) * (float) $product->cost_price, 2)];
        })->sortBy(1)->values();

        $rows->push(['', 'TOTAUX', '', null, round((float) $rows->sum(4), 3), round((float) $rows->sum(5), 3), null, '', (int) $rows->sum(8), round((float) $rows->sum(9), 2)]);

        return [
            'kpis' => [
                ['label' => 'Articles mouvementés', 'value' => (string) ($rows->count() - 1)],
                ['label' => 'Quantités entrées', 'value' => number_format((float) $rows->last()[4], 0, ',', ' '), 'tone' => 'green'],
                ['label' => 'Quantités sorties', 'value' => number_format((float) $rows->last()[5], 0, ',', ' '), 'tone' => 'red'],
                ['label' => 'Variation de valeur', 'value' => Money::format($rows->last()[9])],
            ],
            'headings' => ['Réf.', 'Article', 'Entrepôt', 'Stock ouverture', 'Entrées', 'Sorties', 'Stock clôture', 'Unité', 'Mouvements', 'Variation valeur'],
            'rows' => $rows->all(),
            'totals' => [$rows->count() - 1],
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function alerts(Collection $warehouses, ?int $categoryId): array
    {
        $levels = StockLevel::query()->with('product.unit', 'product.supplier', 'warehouse')->whereIn('warehouse_id', $warehouses)
            ->whereHas('product', fn (Builder $q) => $q->where('is_active', true)->where('is_stockable', true)->when($categoryId, fn (Builder $p) => $p->where('product_category_id', $categoryId)))
            ->get()
            ->filter(fn (StockLevel $l) => (float) $l->quantity <= 0 || (float) $l->quantity <= (float) $l->product->min_stock);

        $rows = $levels->map(function (StockLevel $l): array {
            $min = (float) $l->product->min_stock;
            $target = $l->product->max_stock ? (float) $l->product->max_stock : max($min * 2, 1);
            $suggested = max(0, round($target - (float) $l->quantity, 3));

            return [$l->product->sku, $l->product->name, $l->warehouse->name, (float) $l->quantity, $min, $suggested, $l->product->unit?->symbol ?? '', $l->product->supplier?->name ?? '-', round($suggested * (float) $l->product->cost_price, 2), (float) $l->quantity <= 0 ? 'Rupture' : 'Sous le seuil'];
        })->sortBy(fn ($r) => ($r[9] === 'Rupture' ? 0 : 1).$r[1])->values();

        $rows->push(['', 'BUDGET DE RÉAPPROVISIONNEMENT', '', null, null, null, '', '', round((float) $rows->sum(8), 2), '']);

        return [
            'kpis' => [
                ['label' => 'Articles en rupture', 'value' => (string) $rows->where(9, 'Rupture')->count(), 'tone' => 'red'],
                ['label' => 'Sous le seuil', 'value' => (string) $rows->where(9, 'Sous le seuil')->count(), 'tone' => 'amber'],
                ['label' => 'Budget de réappro.', 'value' => Money::format($rows->last()[8])],
            ],
            'headings' => ['Réf.', 'Article', 'Entrepôt', 'Stock actuel', 'Seuil min.', 'À commander', 'Unité', 'Fournisseur', 'Coût estimé', 'Statut'],
            'rows' => $rows->all(),
            'totals' => [$rows->count() - 1],
            'statusColumn' => 9,
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function byType(Collection $warehouses, Carbon $start, Carbon $end): array
    {
        $rows = StockMovement::query()->whereIn('warehouse_id', $warehouses)->whereBetween('moved_at', [$start, $end])
            ->groupBy('type')
            ->selectRaw('type, COUNT(*) as moves, SUM(ABS(quantity)) as qty, SUM(ABS(quantity) * unit_cost) as value, COUNT(DISTINCT product_id) as products')
            ->toBase()->get()
            ->map(function ($r): array {
                $type = StockMovementType::from($r->type);

                return [$type->label(), match ($type->direction()) {
                    1 => 'Entrée', -1 => 'Sortie', default => 'Ajustement'
                }, (int) $r->moves, (int) $r->products, round((float) $r->qty, 3), round((float) $r->value, 2)];
            })->sortBy([[1, 'asc'], [5, 'desc']])->values();

        $entries = (float) $rows->where(1, 'Entrée')->sum(5);
        $exits = (float) $rows->where(1, 'Sortie')->sum(5);
        $rows->push(['TOTAL', '', (int) $rows->sum(2), null, round((float) $rows->sum(4), 3), round((float) $rows->sum(5), 2)]);

        return [
            'kpis' => [
                ['label' => 'Valeur entrée', 'value' => Money::format($entries), 'tone' => 'green'],
                ['label' => 'Valeur sortie', 'value' => Money::format($exits), 'tone' => 'red'],
                ['label' => 'Solde de la période', 'value' => Money::format($entries - $exits)],
            ],
            'headings' => ['Nature', 'Sens', 'Mouvements', 'Articles', 'Quantité', 'Valeur'],
            'rows' => $rows->all(),
            'totals' => [$rows->count() - 1],
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function dormant(Collection $warehouses, ?int $categoryId, int $days = 60): array
    {
        $lastMoves = StockMovement::query()->whereIn('warehouse_id', $warehouses)->groupBy('product_id', 'warehouse_id')
            ->selectRaw('product_id, warehouse_id, MAX(moved_at) as last_move')->toBase()->get()->keyBy(fn ($r) => $r->product_id.'-'.$r->warehouse_id);
        $limit = now()->subDays($days);

        $rows = StockLevel::query()->with('product.unit', 'warehouse')->whereIn('warehouse_id', $warehouses)->where('quantity', '>', 0)
            ->when($categoryId, fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('product_category_id', $categoryId)))
            ->get()
            ->filter(function (StockLevel $l) use ($lastMoves, $limit): bool {
                $last = $lastMoves[$l->product_id.'-'.$l->warehouse_id]->last_move ?? null;

                return $last === null || Carbon::parse($last)->lt($limit);
            })
            ->map(function (StockLevel $l) use ($lastMoves): array {
                $last = $lastMoves[$l->product_id.'-'.$l->warehouse_id]->last_move ?? null;

                return [$l->product->sku, $l->product->name, $l->warehouse->name, (float) $l->quantity, $l->product->unit?->symbol ?? '', $last ? Carbon::parse($last)->format('d/m/Y') : 'Jamais', $last ? (int) Carbon::parse($last)->diffInDays(now()) : null, round((float) $l->quantity * (float) $l->product->cost_price, 2)];
            })->sortByDesc(7)->values();

        $rows->push(['', 'CAPITAL IMMOBILISÉ', '', null, '', '', null, round((float) $rows->sum(7), 2)]);

        return [
            'kpis' => [
                ['label' => 'Articles dormants', 'value' => (string) ($rows->count() - 1), 'tone' => 'amber'],
                ['label' => 'Capital immobilisé', 'value' => Money::format($rows->last()[7])],
                ['label' => 'Seuil', 'value' => "{$days} jours sans mouvement"],
            ],
            'headings' => ['Réf.', 'Article', 'Entrepôt', 'Quantité', 'Unité', 'Dernier mouvement', 'Jours', 'Valeur'],
            'rows' => $rows->all(),
            'totals' => [$rows->count() - 1],
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function people(Collection $warehouses, Carbon $start, Carbon $end): array
    {
        $movements = StockMovement::query()->with('user', 'source')->whereIn('warehouse_id', $warehouses)->whereBetween('moved_at', [$start, $end])->get();
        $direction = fn (StockMovement $m) => $m->type->direction() === 0 ? ((float) $m->quantity >= 0 ? 1 : -1) : $m->type->direction();

        $rows = $movements->groupBy('user_id')->map(fn (Collection $items) => [
            $items->first()->user?->name ?? 'Système',
            'Enregistrement',
            $items->count(),
            $items->filter(fn ($m) => $direction($m) > 0)->count(),
            $items->filter(fn ($m) => $direction($m) < 0)->count(),
            $items->pluck('product_id')->unique()->count(),
            $items->max('moved_at')->format('d/m/Y H:i'),
        ])->values();

        $performers = $movements->filter(fn (StockMovement $m) => filled($m->performerName()))->groupBy(fn (StockMovement $m) => $m->performerName())
            ->map(fn (Collection $items, string $name) => [
                $name, 'Livraison / retrait', $items->count(),
                $items->filter(fn ($m) => $direction($m) > 0)->count(),
                $items->filter(fn ($m) => $direction($m) < 0)->count(),
                $items->pluck('product_id')->unique()->count(),
                $items->max('moved_at')->format('d/m/Y H:i'),
            ])->values();

        $rows = $rows->concat($performers)->sortByDesc(2)->values();

        return [
            'kpis' => [
                ['label' => 'Intervenants', 'value' => (string) $rows->count()],
                ['label' => 'Mouvements', 'value' => (string) $movements->count()],
            ],
            'headings' => ['Personne', 'Rôle', 'Mouvements', 'Entrées', 'Sorties', 'Articles', 'Dernière action'],
            'rows' => $rows->all(),
            'totals' => [],
        ];
    }

    /**
     * @param  Collection<int, int>  $warehouses
     * @return array<string, mixed>
     */
    private function transfers(Collection $warehouses, Carbon $start, Carbon $end): array
    {
        $transfers = StockTransfer::query()->with('from', 'to', 'items', 'requester', 'shipper', 'receiver')
            ->where(fn (Builder $q) => $q->whereIn('from_warehouse_id', $warehouses)->orWhereIn('to_warehouse_id', $warehouses))
            ->whereBetween('requested_at', [$start, $end])->latest('requested_at')->get();

        $rows = $transfers->map(fn (StockTransfer $t) => [
            $t->number, $t->requested_at->format('d/m/Y'), $t->from->name, $t->to->name, $t->status->label(),
            $t->items->count(), round((float) $t->items->sum('quantity_requested'), 3),
            round((float) $t->items->sum(fn ($i) => (float) ($i->quantity_received ?? 0) - (float) ($i->quantity_shipped ?? $i->quantity_requested)), 3),
            $t->shipper?->name ?? '-', $t->receiver?->name ?? '-',
        ])->values();

        return [
            'kpis' => [
                ['label' => 'Transferts', 'value' => (string) $transfers->count()],
                ['label' => 'En cours', 'value' => (string) $transfers->filter(fn ($t) => ! in_array($t->status->value, ['received', 'cancelled'], true))->count(), 'tone' => 'amber'],
                ['label' => 'Avec écart', 'value' => (string) $rows->filter(fn ($r) => $r[4] !== 'Annulé' && abs((float) $r[7]) > 0 && $r[9] !== '-')->count(), 'tone' => 'red'],
            ],
            'headings' => ['N°', 'Date', 'De', 'Vers', 'Statut', 'Articles', 'Qté demandée', 'Écart réception', 'Expédié par', 'Reçu par'],
            'rows' => $rows->all(),
            'totals' => [],
        ];
    }
}
