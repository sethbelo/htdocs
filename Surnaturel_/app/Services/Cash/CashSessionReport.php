<?php

namespace App\Services\Cash;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\SaleItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Services\Accounting\AccountingSyncService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Situation complète d'une session de caisse (rapport X en cours, rapport Z à la clôture).
 */
class CashSessionReport
{
    public function __construct(private AccountingSyncService $sync) {}

    /**
     * @return array<string, mixed>
     */
    public function build(CashSession $session): array
    {
        $session->loadMissing('register.site', 'register.warehouse', 'register.treasuryAccount', 'cashier', 'closer', 'supervisor', 'depositAccount');
        $end = $session->closed_at ?? now();

        $sales = $session->sales()->with('items')->get();
        $completed = $sales->where('status.value', 'completed');
        $payments = $session->payments()->where('direction', 'in')->get();

        $byMethod = collect(PaymentMethod::cases())
            ->reject(fn (PaymentMethod $method): bool => $method === PaymentMethod::Credit)
            ->map(fn (PaymentMethod $method): array => [
                'method' => $method,
                'sales' => round((float) $payments->where('method', $method)->where('payable_type', 'sale')->sum('amount'), 2),
                'orders' => round((float) $payments->where('method', $method)->where('payable_type', 'order')->sum('amount'), 2),
            ])
            ->map(fn (array $row): array => $row + ['total' => round($row['sales'] + $row['orders'], 2)])
            ->filter(fn (array $row): bool => $row['total'] != 0.0)
            ->values();

        $disbursements = $session->movements()->with('authorizer', 'user')->where('type', CashMovementType::Disbursement)->get();
        $warehouseId = $session->register->warehouse_id;

        $topProducts = SaleItem::query()->whereIn('sale_id', $completed->pluck('id'))
            ->selectRaw('description, SUM(quantity) as quantity, SUM(total) as total')
            ->groupBy('description')->orderByDesc('total')->limit(8)->get();

        $stockMovements = $warehouseId
            ? StockMovement::query()->where('warehouse_id', $warehouseId)->whereBetween('moved_at', [$session->opened_at, $end])
                ->selectRaw('type, COUNT(*) as count, SUM(quantity) as quantity')->groupBy('type')->get()
            : collect();

        $expected = $session->closed_at ? (float) $session->expected_balance : $session->theoreticalCashBalance();
        $salesTotal = round((float) $completed->sum('total'), 2);
        $creditSales = round($completed->sum(fn ($sale): float => max(0, (float) $sale->total - (float) $sale->amount_paid + (float) $sale->change_due)), 2);

        return [
            'session' => $session,
            'duration' => $session->opened_at->diffForHumans($end, ['parts' => 2, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]),
            'warehouse' => $session->register->warehouse,
            'opening' => (float) $session->opening_balance,
            'sales_count' => $completed->count(),
            'voided_count' => $sales->count() - $completed->count(),
            'sales_total' => $salesTotal,
            'sales_subtotal' => round((float) $completed->sum('subtotal'), 2),
            'discounts' => round((float) $completed->sum('discount_total'), 2),
            'tax' => round((float) $completed->sum('tax_total'), 2),
            'average_basket' => $completed->count() ? round($salesTotal / $completed->count(), 2) : 0.0,
            'items_sold' => round((float) $completed->flatMap->items->sum('quantity'), 3),
            'change_given' => round((float) $completed->sum('change_due'), 2),
            'credit_sales' => $creditSales,
            'loyalty_points' => (int) $completed->sum('loyalty_points_earned'),
            'orders_count' => $payments->where('payable_type', 'order')->pluck('payable_id')->unique()->count(),
            'orders_total' => round((float) $payments->where('payable_type', 'order')->sum('amount'), 2),
            'by_method' => $byMethod,
            'collected_total' => round((float) $payments->sum('amount'), 2),
            'disbursements' => $disbursements,
            'disbursements_total' => round((float) $disbursements->sum('amount'), 2),
            'top_products' => $topProducts,
            'stock_movements' => $stockMovements,
            'low_stock' => $warehouseId ? $this->lowStock($warehouseId) : collect(),
            'expected_cash' => round($expected, 2),
            'accounting' => $this->sync->status($session),
            'payments_count' => $payments->count(),
        ];
    }

    /**
     * @return Collection<int, StockLevel>
     */
    private function lowStock(int $warehouseId): Collection
    {
        return StockLevel::query()->with('product')
            ->where('warehouse_id', $warehouseId)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select('stock_levels.*')
            ->orderBy('stock_levels.quantity')
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string|int, int|string|null>  $denominations
     */
    public static function countDenominations(array $denominations): float
    {
        return round(collect($denominations)->sum(fn ($count, $value): float => (float) $value * max(0, (int) $count)), 2);
    }
}
