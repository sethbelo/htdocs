<?php

namespace App\Livewire\Admin\Dashboard;

use App\Enums\OrderStatus;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Index extends Component
{
    public string $period = 'today';

    public float $todayRevenue = 0;

    public float $monthRevenue = 0;

    public float $selectedRevenue = 0;

    public int $todayOrdersCount = 0;

    public int $selectedOrdersCount = 0;

    public float $averageBasket = 0;

    public float $revenueChange = 0;

    public float $ordersChange = 0;

    public float $basketChange = 0;

    public int $totalProducts = 0;

    public int $totalCategories = 0;

    public int $totalCustomers = 0;

    public int $activeUsers = 0;

    public int $lowStockCount = 0;

    public int $outOfStockCount = 0;

    public int $activeOrdersCount = 0;

    public int $openCashSessionsCount = 0;

    public string $periodLabel = 'Aujourd’hui';

    public array $salesChart = [];

    public array $topProducts = [];

    public array $recentOrders = [];

    public array $lowStockItems = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function updatedPeriod(): void
    {
        if (! in_array($this->period, ['today', 'week', 'month', 'year'], true)) {
            $this->period = 'today';
        }

        $this->loadDashboard();
    }

    public function refreshDashboard(): void
    {
        $this->loadDashboard();
        $this->dispatch('dashboard-refreshed');
    }

    public function loadDashboard(): void
    {
        $establishment = auth()->user()->establishment;
        if (! $establishment) {
            return;
        }

        [$start, $end, $previousStart, $previousEnd, $this->periodLabel] = $this->periodRange();
        $establishmentId = $establishment->id;

        $currentOrders = $this->paidOrders($establishmentId)->whereBetween('created_at', [$start, $end]);
        $previousOrders = $this->paidOrders($establishmentId)->whereBetween('created_at', [$previousStart, $previousEnd]);

        $this->selectedRevenue = (float) (clone $currentOrders)->sum('total');
        $this->selectedOrdersCount = (clone $currentOrders)->count();
        $this->averageBasket = $this->selectedOrdersCount > 0 ? $this->selectedRevenue / $this->selectedOrdersCount : 0;

        $previousRevenue = (float) (clone $previousOrders)->sum('total');
        $previousCount = (clone $previousOrders)->count();
        $previousBasket = $previousCount > 0 ? $previousRevenue / $previousCount : 0;

        $this->revenueChange = $this->percentageChange($this->selectedRevenue, $previousRevenue);
        $this->ordersChange = $this->percentageChange($this->selectedOrdersCount, $previousCount);
        $this->basketChange = $this->percentageChange($this->averageBasket, $previousBasket);

        $this->todayRevenue = (float) $this->paidOrders($establishmentId)->whereBetween('created_at', [today(), now()])->sum('total');
        $this->monthRevenue = (float) $this->paidOrders($establishmentId)->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('total');
        $this->todayOrdersCount = $this->paidOrders($establishmentId)->whereBetween('created_at', [today(), now()])->count();

        $this->totalProducts = Product::where('establishment_id', $establishmentId)->active()->count();
        $this->totalCategories = Category::where('establishment_id', $establishmentId)->active()->count();
        $this->totalCustomers = Customer::where('establishment_id', $establishmentId)->count();
        $this->activeUsers = User::where('establishment_id', $establishmentId)->where('status', 'active')->count();

        $stockQuery = Stock::query()->whereHas('warehouse', fn (Builder $query) => $query->where('establishment_id', $establishmentId));
        $this->lowStockCount = (clone $stockQuery)->lowStock()->count();
        $this->outOfStockCount = (clone $stockQuery)->outOfStock()->count();
        $this->activeOrdersCount = Order::where('establishment_id', $establishmentId)->active()->count();
        $this->openCashSessionsCount = CashSession::query()
            ->where('status', 'open')
            ->whereHas('cashRegister', fn (Builder $query) => $query->where('establishment_id', $establishmentId))
            ->count();

        $ordersForChart = (clone $currentOrders)->get(['created_at', 'total']);
        $this->salesChart = $this->buildSalesChart($ordersForChart, $start);
        $this->loadPreviews($establishmentId, $start, $end);
    }

    protected function paidOrders(string $establishmentId): Builder
    {
        return Order::query()
            ->where('establishment_id', $establishmentId)
            ->where('is_paid', true)
            ->where('status', '!=', OrderStatus::Cancelled->value);
    }

    protected function periodRange(): array
    {
        $now = now();

        return match ($this->period) {
            'week' => [$now->copy()->startOfWeek(), $now, $now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek(), 'Cette semaine'],
            'month' => [$now->copy()->startOfMonth(), $now, $now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), 'Ce mois'],
            'year' => [$now->copy()->startOfYear(), $now, $now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear(), 'Cette année'],
            default => [$now->copy()->startOfDay(), $now, $now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Aujourd’hui'],
        };
    }

    protected function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0 : 100;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    protected function buildSalesChart($orders, Carbon $start): array
    {
        $buckets = match ($this->period) {
            'week' => collect(range(0, 6))->map(fn ($index) => ['key' => $start->copy()->addDays($index)->toDateString(), 'label' => mb_ucfirst($start->copy()->addDays($index)->translatedFormat('D'))]),
            'month' => collect(range(0, (int) ceil($start->daysInMonth / 3) - 1))->map(fn ($index) => ['key' => (string) $index, 'label' => (string) ($index * 3 + 1)]),
            'year' => collect(range(1, 12))->map(fn ($month) => ['key' => (string) $month, 'label' => mb_ucfirst(Carbon::create(null, $month, 1)->translatedFormat('M'))]),
            default => collect(range(0, 7))->map(fn ($index) => ['key' => (string) $index, 'label' => str_pad((string) ($index * 3), 2, '0', STR_PAD_LEFT).'h']),
        };

        $values = $buckets->mapWithKeys(fn ($bucket) => [$bucket['key'] => 0.0]);
        foreach ($orders as $order) {
            $createdAt = $order->created_at;
            $key = match ($this->period) {
                'week' => $createdAt->toDateString(),
                'month' => (string) intdiv($createdAt->day - 1, 3),
                'year' => (string) $createdAt->month,
                default => (string) intdiv($createdAt->hour, 3),
            };
            if ($values->has($key)) {
                $values[$key] += (float) $order->total;
            }
        }

        $maximum = max(1, (float) $values->max());

        return $buckets->map(fn ($bucket) => [
            'label' => $bucket['label'],
            'value' => round($values[$bucket['key']] ?? 0, 2),
            'height' => max(3, round((($values[$bucket['key']] ?? 0) / $maximum) * 100, 1)),
        ])->values()->all();
    }

    protected function loadPreviews(string $establishmentId, Carbon $start, Carbon $end): void
    {
        $this->topProducts = OrderItem::query()
            ->select(['order_items.product_id', 'order_items.name'])
            ->selectRaw('SUM(order_items.quantity) as sold_quantity, SUM(order_items.total) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.establishment_id', $establishmentId)
            ->where('orders.is_paid', true)
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('orders.created_at', [$start, $end])
            ->groupBy('order_items.product_id', 'order_items.name')
            ->orderByDesc('sold_quantity')->limit(5)->get()
            ->map(fn ($item) => ['name' => $item->name, 'quantity' => (float) $item->sold_quantity, 'revenue' => (float) $item->revenue])
            ->all();

        $this->recentOrders = Order::query()
            ->where('establishment_id', $establishmentId)
            ->with(['customer', 'table', 'user'])
            ->latest()->limit(5)->get()
            ->map(fn (Order $order) => [
                'number' => $order->order_number,
                'customer' => $order->customer?->full_name ?? ($order->table ? 'Table '.$order->table->name : 'Client comptoir'),
                'total' => (float) $order->total,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'time' => $order->created_at->diffForHumans(short: true),
            ])->all();

        $this->lowStockItems = Stock::query()
            ->whereHas('warehouse', fn (Builder $query) => $query->where('establishment_id', $establishmentId))
            ->whereColumn('quantity', '<=', 'alert_quantity')
            ->with(['product', 'warehouse'])
            ->orderBy('quantity')->limit(5)->get()
            ->map(fn (Stock $stock) => [
                'name' => $stock->product?->name ?? 'Produit',
                'warehouse' => $stock->warehouse?->name ?? 'Dépôt',
                'quantity' => (float) $stock->quantity,
                'alert' => (float) $stock->alert_quantity,
                'out' => (float) $stock->quantity <= 0,
            ])->all();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.index')
            ->layout('layouts.admin', ['title' => 'Tableau de bord']);
    }
}
