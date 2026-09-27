<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'today';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatedPeriod(): void
    {
        match ($this->period) {
            'today' => $this->setDates(now(), now()),
            'yesterday' => $this->setDates(now()->subDay(), now()->subDay()),
            'week' => $this->setDates(now()->startOfWeek(), now()),
            'month' => $this->setDates(now()->startOfMonth(), now()),
            'quarter' => $this->setDates(now()->startOfQuarter(), now()),
            'year' => $this->setDates(now()->startOfYear(), now()),
            'custom' => null,
            default => null,
        };
    }

    private function setDates(Carbon $from, Carbon $to): void
    {
        $this->dateFrom = $from->format('Y-m-d');
        $this->dateTo = $to->format('Y-m-d');
    }

    public function render()
    {
        $eid = auth()->user()->establishment_id;
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        // ── KPIs ──────────────────────────────
        $orders = Order::where('establishment_id', $eid)
            ->whereBetween('created_at', [$from, $to]);

        $totalRevenue = (clone $orders)->where('is_paid', true)->sum('total');
        $orderCount = (clone $orders)->count();
        $avgTicket = $orderCount > 0 ? $totalRevenue / $orderCount : 0;
        $paidOrders = (clone $orders)->where('is_paid', true)->count();

        $totalExpenses = Expense::where('establishment_id', $eid)
            ->approved()
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');

        $profit = $totalRevenue - $totalExpenses;
        $customerCount = (clone $orders)->whereNotNull('customer_id')->distinct('customer_id')->count();

        // ── Revenue by Day (Chart) ──────────────────────────────
        $revenueByDay = Order::where('establishment_id', $eid)
            ->where('is_paid', true)
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // ── Revenue by Payment Method ──────────────────────────────
        $revenueByMethod = Payment::where('payments.establishment_id', $eid)
            ->whereBetween('payments.created_at', [$from, $to])
            ->where('payments.status', 'completed')
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->get();

        // ── Top Products ──────────────────────────────
        $topProducts = OrderItem::whereHas('order', fn ($q) => $q
            ->where('establishment_id', $eid)
            ->where('is_paid', true)
            ->whereBetween('created_at', [$from, $to])
        )
            ->select('product_id', 'name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total) as revenue'))
            ->groupBy('product_id', 'name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // ── Revenue by Order Type ──────────────────────────────
        $revenueByType = Order::where('establishment_id', $eid)
            ->where('is_paid', true)
            ->whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        // ── Revenue by Hour ──────────────────────────────
        $driver = DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite'
            ? "CAST(strftime('%H', created_at) AS INTEGER)"
            : 'HOUR(created_at)';

        $revenueByHour = Order::where('establishment_id', $eid)
            ->where('is_paid', true)
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw("{$hourExpr} as hour"), DB::raw('SUM(total) as revenue'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // ── Expense Categories ──────────────────────────────
        $expensesByCategory = Expense::where('establishment_id', $eid)
            ->approved()
            ->whereBetween('expense_date', [$from, $to])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // ── Recent Orders ──────────────────────────────
        $recentOrders = Order::where('establishment_id', $eid)
            ->whereBetween('created_at', [$from, $to])
            ->with(['customer', 'user', 'items'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // ── Staff Performance ──────────────────────────────
        $staffPerformance = Order::where('orders.establishment_id', $eid)
            ->where('orders.is_paid', true)
            ->whereBetween('orders.created_at', [$from, $to])
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('COUNT(orders.id) as order_count'), DB::raw('SUM(orders.total) as revenue'))
            ->groupBy('users.name')
            ->orderByDesc('revenue')
            ->get();

        return view('livewire.admin.reports.dashboard', compact(
            'totalRevenue', 'orderCount', 'avgTicket', 'paidOrders',
            'totalExpenses', 'profit', 'customerCount',
            'revenueByDay', 'revenueByMethod', 'topProducts',
            'revenueByType', 'revenueByHour', 'expensesByCategory',
            'recentOrders', 'staffPerformance'
        ))->layout('layouts.admin', ['title' => 'Rapports & Analytique']);
    }
}
