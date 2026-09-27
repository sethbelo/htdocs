<?php

namespace App\Services\Inventory;

use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rapport d'entrepôt : toutes les entrées et sorties d'une journée (ou d'une période),
 * avec la personne qui a enregistré et celle qui a effectué chaque mouvement.
 */
class WarehouseReport
{
    /**
     * @return array{movements: Collection<int, StockMovement>, kpis: array<string, float|int>, products: Collection<int, array<string, mixed>>, byUser: Collection<int, array<string, mixed>>, hourly: array<int, array{in: float, out: float}>}
     */
    public function build(User $user, ?int $warehouseId, string $from, string $to, string $direction = ''): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $movements = StockMovement::query()
            ->with('product.unit', 'warehouse', 'relatedWarehouse', 'user', 'source')
            ->whereHas('warehouse', fn (Builder $q) => $q->visibleTo($user))
            ->when($warehouseId, fn (Builder $q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('moved_at', [$start, $end])
            ->latest('moved_at')->latest('id')
            ->get()
            ->each(function (StockMovement $m): void {
                $direction = $m->type->direction();
                $m->setAttribute('signed_quantity', $direction === 0 ? (float) $m->quantity : $direction * abs((float) $m->quantity));
                $m->setAttribute('value', abs((float) $m->quantity) * (float) $m->unit_cost);
            })
            ->when($direction === 'in', fn (Collection $c) => $c->filter(fn ($m) => $m->signed_quantity > 0)->values())
            ->when($direction === 'out', fn (Collection $c) => $c->filter(fn ($m) => $m->signed_quantity < 0)->values());

        $entries = $movements->filter(fn ($m) => $m->signed_quantity > 0);
        $exits = $movements->filter(fn ($m) => $m->signed_quantity < 0);

        $hourly = array_fill(0, 24, ['in' => 0.0, 'out' => 0.0]);
        foreach ($movements as $m) {
            $hourly[(int) $m->moved_at->format('G')][$m->signed_quantity > 0 ? 'in' : 'out'] += abs($m->signed_quantity);
        }

        return [
            'movements' => $movements,
            'kpis' => [
                'count' => $movements->count(),
                'entries' => $entries->count(),
                'exits' => $exits->count(),
                'qty_in' => (float) $entries->sum('signed_quantity'),
                'qty_out' => (float) abs($exits->sum('signed_quantity')),
                'value_in' => (float) $entries->sum('value'),
                'value_out' => (float) $exits->sum('value'),
                'products' => $movements->pluck('product_id')->unique()->count(),
                'people' => $movements->pluck('user_id')->filter()->unique()->count(),
            ],
            'products' => $movements->groupBy(fn ($m) => $m->warehouse_id.'-'.$m->product_id)->map(function (Collection $items) {
                $ordered = $items->sortBy(fn ($m) => sprintf('%s-%012d', $m->moved_at->format('YmdHis'), $m->id))->values();
                $first = $ordered->first();
                $last = $ordered->last();

                return [
                    'product' => $first->product,
                    'warehouse' => $first->warehouse,
                    'opening' => (float) $first->balance_after - (float) $first->signed_quantity,
                    'in' => (float) $items->where('signed_quantity', '>', 0)->sum('signed_quantity'),
                    'out' => (float) abs($items->where('signed_quantity', '<', 0)->sum('signed_quantity')),
                    'closing' => (float) $last->balance_after,
                    'count' => $items->count(),
                ];
            })->sortBy(fn ($row) => $row['product']->name)->values(),
            'byUser' => $movements->groupBy('user_id')->map(fn (Collection $items) => [
                'name' => $items->first()->user?->name ?? 'Système',
                'count' => $items->count(),
                'in' => $items->where('signed_quantity', '>', 0)->count(),
                'out' => $items->where('signed_quantity', '<', 0)->count(),
                'last' => $items->max('moved_at'),
            ])->sortByDesc('count')->values(),
            'hourly' => $hourly,
        ];
    }

    public function sourceReference(StockMovement $movement): ?string
    {
        return $movement->source?->number ?? null;
    }

    public function warehouseLabel(?int $warehouseId): string
    {
        return $warehouseId ? (string) Warehouse::query()->find($warehouseId)?->name : 'Tous les entrepôts';
    }
}
