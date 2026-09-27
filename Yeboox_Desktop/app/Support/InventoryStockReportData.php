<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class InventoryStockReportData
{
    public static function query(?string $search = null, ?string $category = null, ?string $status = null): Builder
    {
        return Product::query()
            ->with('unitOfMeasure')
            ->where('type', 'product')
            ->when($search, fn (Builder $query, string $term) => $query->where(
                fn (Builder $searchQuery) => $searchQuery
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
            ))
            ->when($category, fn (Builder $query, string $value) => $query->where('category', $value))
            ->when($status === 'in_stock', fn (Builder $query) => $query->whereColumn('stock_qty', '>', 'alert_qty'))
            ->when($status === 'alert', fn (Builder $query) => $query->whereColumn('stock_qty', '<=', 'alert_qty')->where('stock_qty', '>', 0))
            ->when($status === 'out', fn (Builder $query) => $query->where('stock_qty', '<=', 0));
    }

    public static function build(
        ?string $search = null,
        ?string $category = null,
        ?string $status = null,
        ?string $start = null,
        ?string $end = null,
    ): array
    {
        $products = self::query($search, $category, $status)->orderBy('category')->orderBy('name')->get();
        $startAt = filled($start) ? Carbon::parse($start)->startOfDay() : now()->startOfMonth();
        $endAt = filled($end) ? Carbon::parse($end)->endOfDay() : now()->endOfDay();
        $categories = $products->groupBy(fn (Product $product) => $product->category ?: 'Sans catégorie')
            ->map(fn ($rows, string $name) => [
                'name' => $name,
                'articles' => $rows->count(),
                'quantity' => (float) $rows->sum('stock_qty'),
                'value' => (float) $rows->sum(fn (Product $product) => (float) $product->stock_qty * (float) $product->cost),
            ])->values();
        $productIds = $products->pluck('id');
        $movements = StockMovement::query()
            ->with(['product.unitOfMeasure', 'user'])
            ->whereBetween('created_at', [$startAt, $endAt])
            ->whereIn('product_id', $productIds)
            ->latest()
            ->get();
        $movementValue = fn (StockMovement $movement): float => (float) $movement->quantity
            * ((float) $movement->unit_cost ?: (float) ($movement->product?->cost ?? 0));
        $entries = $movements->where('type', 'in');
        $exits = $movements->where('type', 'out');
        $supplies = $movements->where('reason', 'supply');

        return [
            'products' => $products,
            'categories' => $categories,
            'totals' => [
                'articles' => $products->count(),
                'quantity' => (float) $products->sum('stock_qty'),
                'value' => (float) $products->sum(fn (Product $product) => (float) $product->stock_qty * (float) $product->cost),
                'alerts' => $products->filter(fn (Product $product) => (float) $product->stock_qty > 0 && (float) $product->stock_qty <= (float) $product->alert_qty)->count(),
                'out' => $products->filter(fn (Product $product) => (float) $product->stock_qty <= 0)->count(),
            ],
            'movements' => $movements,
            'movementTotals' => [
                'count' => $movements->count(),
                'entries_quantity' => (float) $entries->sum('quantity'),
                'entries_value' => (float) $entries->sum($movementValue),
                'exits_quantity' => (float) $exits->sum('quantity'),
                'exits_value' => (float) $exits->sum($movementValue),
                'supplies_quantity' => (float) $supplies->sum('quantity'),
                'supplies_value' => (float) $supplies->sum($movementValue),
            ],
            'period' => ['start' => $startAt, 'end' => $endAt],
        ];
    }
}
