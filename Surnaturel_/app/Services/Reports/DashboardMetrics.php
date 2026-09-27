<?php

namespace App\Services\Reports;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PrintJobStatus;
use App\Enums\ProofStatus;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrintJob;
use App\Models\Proof;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Site;
use App\Models\StockLevel;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Indicateurs du tableau de bord, filtrés selon les sièges accessibles (ou un siège choisi).
 */
class DashboardMetrics
{
    /** @var list<int> */
    private array $siteIds;

    public function __construct(private User $user, ?int $siteId = null)
    {
        $accessible = $user->accessibleSiteIds();
        $this->siteIds = $siteId && in_array($siteId, $accessible, true) ? [$siteId] : $accessible;
    }

    /**
     * @return array<string, float|int>
     */
    public function kpis(): array
    {
        $today = today();
        $orders = fn (): Builder => Order::query()->whereIn('site_id', $this->siteIds);

        return [
            'revenue_today' => $this->revenueBetween($today, $today->copy()->endOfDay()),
            'revenue_month' => $this->revenueBetween($today->copy()->startOfMonth(), $today->copy()->endOfDay()),
            'orders_today' => $orders()->whereDate('ordered_at', $today)->count(),
            'orders_awaiting_payment' => $orders()->where('status', OrderStatus::AwaitingPayment)->count(),
            'orders_in_design' => $orders()->where('status', OrderStatus::InDesign)->count(),
            'proofs_pending' => Proof::query()->where('status', ProofStatus::Pending)->whereHas('order', fn (Builder $q) => $q->whereIn('site_id', $this->siteIds))->count(),
            'printing' => PrintJob::query()->whereIn('status', [PrintJobStatus::Preparing, PrintJobStatus::Printing, PrintJobStatus::Finishing, PrintJobStatus::QualityCheck])->whereHas('order', fn (Builder $q) => $q->whereIn('site_id', $this->siteIds))->count(),
            'orders_ready' => $orders()->where('status', OrderStatus::Ready)->count(),
            'deliveries' => Delivery::query()->whereIn('status', [DeliveryStatus::Pending, DeliveryStatus::InProgress])->whereHas('order', fn (Builder $q) => $q->whereIn('site_id', $this->siteIds))->count(),
            'shop_sales_today' => (float) Sale::query()->whereIn('site_id', $this->siteIds)->whereDate('sold_at', $today)->where('status', 'completed')->sum('total'),
            'expenses_month' => (float) Payment::query()->whereIn('site_id', $this->siteIds)->where('direction', 'out')->where('paid_at', '>=', $today->copy()->startOfMonth())->sum('amount'),
            'treasury' => (float) TreasuryAccount::query()->where(fn (Builder $q) => $q->whereIn('site_id', $this->siteIds)->orWhereNull('site_id'))->sum('balance'),
            'receivables' => (float) Invoice::query()->whereIn('site_id', $this->siteIds)->whereIn('status', ['issued', 'partially_paid'])->selectRaw('COALESCE(SUM(total - amount_paid), 0) as due')->value('due'),
            'low_stock' => $this->lowStock()->count(),
        ];
    }

    public function revenueBetween(Carbon $from, Carbon $to): float
    {
        return round((float) Payment::query()->whereIn('site_id', $this->siteIds)->where('direction', 'in')->whereBetween('paid_at', [$from, $to])->sum('amount'), 2);
    }

    /**
     * @return Collection<int, StockLevel>
     */
    public function lowStock(): Collection
    {
        $warehouseIds = Warehouse::query()->whereIn('site_id', $this->siteIds)->pluck('id');

        return StockLevel::query()
            ->with('product', 'warehouse')
            ->whereIn('warehouse_id', $warehouseIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.min_stock')
            ->where('products.is_active', true)
            ->select('stock_levels.*')
            ->orderBy('stock_levels.quantity')
            ->limit(10)
            ->get();
    }

    /**
     * Encaissements, ventes boutique et dépenses sur les 30 derniers jours.
     *
     * @return array{labels: list<string>, income: list<float>, shop: list<float>, expenses: list<float>}
     */
    public function dailySeries(int $days = 30): array
    {
        $from = today()->subDays($days - 1);
        $format = fn (Builder $query, string $column, string $sum): Collection => $query
            ->where($column, '>=', $from)
            ->get([$column, $sum])
            ->groupBy(fn ($row) => $row->{$column}->toDateString())
            ->map(fn (Collection $rows): float => round((float) $rows->sum($sum), 2));

        $income = $format(Payment::query()->whereIn('site_id', $this->siteIds)->where('direction', 'in'), 'paid_at', 'amount');
        $expenses = $format(Payment::query()->whereIn('site_id', $this->siteIds)->where('direction', 'out'), 'paid_at', 'amount');
        $shop = $format(Sale::query()->whereIn('site_id', $this->siteIds)->where('status', 'completed'), 'sold_at', 'total');

        $labels = $incomeSeries = $shopSeries = $expenseSeries = [];
        foreach (range(0, $days - 1) as $offset) {
            $day = $from->copy()->addDays($offset)->toDateString();
            $labels[] = Carbon::parse($day)->translatedFormat('d M');
            $incomeSeries[] = $income[$day] ?? 0;
            $shopSeries[] = $shop[$day] ?? 0;
            $expenseSeries[] = $expenses[$day] ?? 0;
        }

        return ['labels' => $labels, 'income' => $incomeSeries, 'shop' => $shopSeries, 'expenses' => $expenseSeries];
    }

    /**
     * @return array<string, int>
     */
    public function ordersByStatus(): array
    {
        return Order::query()->whereIn('site_id', $this->siteIds)
            ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status): array => [OrderStatus::from($status)->label() => (int) $total])
            ->all();
    }

    /**
     * Performance par siège : chiffre d'affaires encaissé du mois.
     *
     * @return array<string, float>
     */
    public function revenueBySite(): array
    {
        return Site::query()->whereIn('id', $this->siteIds)->get()
            ->mapWithKeys(fn (Site $site): array => [$site->name => round((float) Payment::query()->where('site_id', $site->id)->where('direction', 'in')->where('paid_at', '>=', today()->startOfMonth())->sum('amount'), 2)])
            ->all();
    }

    /**
     * Marge boutique du mois : CA HT - coût de revient des articles vendus.
     *
     * @return array{revenue: float, cost: float, margin: float}
     */
    public function shopMargin(): array
    {
        $sales = Sale::query()->whereIn('site_id', $this->siteIds)->where('status', 'completed')->where('sold_at', '>=', today()->startOfMonth());
        $revenue = (float) (clone $sales)->sum('total') - (float) (clone $sales)->sum('tax_total');
        $cost = (float) SaleItem::query()->whereIn('sale_id', (clone $sales)->select('id'))->selectRaw('COALESCE(SUM(unit_cost * quantity), 0) as cost')->value('cost');

        return ['revenue' => round($revenue, 2), 'cost' => round($cost, 2), 'margin' => round($revenue - $cost, 2)];
    }

    /**
     * @return list<array{name: string, kind: string, lat: float, lng: float, color: string}>
     */
    public function mapPoints(): array
    {
        $colors = ['headquarters' => '#4f46e5', 'branch' => '#0284c7', 'shop' => '#059669', 'point_of_sale' => '#d97706'];

        $sites = Site::query()->whereIn('id', $this->siteIds)->whereNotNull('latitude')->get()
            ->map(fn (Site $site): array => ['name' => $site->name, 'kind' => $site->type->label(), 'lat' => $site->latitude, 'lng' => $site->longitude, 'color' => $colors[$site->type->value]]);

        $warehouses = Warehouse::query()->whereIn('site_id', $this->siteIds)->whereNotNull('latitude')->get()
            ->map(fn (Warehouse $warehouse): array => ['name' => $warehouse->name, 'kind' => 'Entrepôt', 'lat' => $warehouse->latitude, 'lng' => $warehouse->longitude, 'color' => '#71717a']);

        return $sites->concat($warehouses)->values()->all();
    }
}
