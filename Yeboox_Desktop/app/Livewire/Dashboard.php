<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public string $period = '7';

    public function refreshDashboard(): void
    {
        // The render cycle refreshes every dashboard metric.
    }

    public function updatedPeriod(string $value): void
    {
        if (! in_array($value, ['7', '14', '30'], true)) {
            $this->period = '7';
        }
    }

    public function render()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $days = (int) $this->period;
        $exchangeRate = (float) Setting::get('exchange_rate', 2250);

        $todayRange = [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
        $yesterdayRange = [$yesterday->copy()->startOfDay(), $yesterday->copy()->endOfDay()];
        $chartStart = $today->copy()->subDays($days - 1)->startOfDay();

        // Une seule agrégation remplace les requêtes répétées pour chaque carte du jour.
        $todaySummary = Sale::whereBetween('created_at', $todayRange)
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as sales_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) as cash_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_method IN ('mobile_money', 'yepay', 'mpesa', 'airtel', 'orange') THEN total_amount ELSE 0 END), 0) as mobile_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_method = 'points' THEN total_amount ELSE 0 END), 0) as points_total")
            ->first();

        $todaySales = (float) $todaySummary->sales_total;
        $yesterdaySales = (float) Sale::whereBetween('created_at', $yesterdayRange)->sum('total_amount');
        $todaySalesCount = (int) $todaySummary->sales_count;
        $salesTrend = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100 : 0);

        $appointments = Appointment::with(['customer', 'employee', 'service'])
            ->whereBetween('date_time', $todayRange)
            ->orderBy('date_time')
            ->get();

        // La courbe est calculée avec une requête groupée, quelle que soit la période.
        $dailySales = Sale::query()
            ->whereBetween('created_at', [$chartStart, $todayRange[1]])
            ->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');
        $chart = collect(range($days - 1, 0))->map(function (int $offset) use ($today, $dailySales) {
            $date = $today->copy()->subDays($offset);

            return [
                'label' => $date->translatedFormat($this->period === '7' ? 'D' : 'd/m'),
                'date' => $date->translatedFormat('d F'),
                'value' => (float) ($dailySales[$date->toDateString()] ?? 0),
            ];
        });

        $maxChartValue = max((float) $chart->max('value'), 1);
        $cashierSummary = Sale::where('user_id', $user->id)->whereBetween('created_at', $todayRange)
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as sales_total')
            ->first();

        return view('livewire.dashboard', [
            'user' => $user,
            'isManager' => $user->canAny(['manage sales', 'manage reports', 'manage inventory']),
            'companyName' => Setting::get('company_name', 'Yeboox'),
            'exchangeRate' => $exchangeRate,
            'todaySales' => $todaySales,
            'todaySalesCount' => $todaySalesCount,
            'salesTrend' => $salesTrend,
            'monthSales' => (float) Sale::whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->sum('total_amount'),
            'appointments' => $appointments,
            'confirmedAppointments' => $appointments->where('status', 'confirmed')->count(),
            'lowStockCount' => Product::where('type', 'product')->whereRaw('stock_qty <= alert_qty')->count(),
            'totalProducts' => Product::count(),
            'activeSession' => RegisterSession::with('user')->where('status', 'open')->first(),
            'totalCustomers' => Customer::count(),
            'activeSubscriptions' => Subscription::where('status', 'active')->where('ends_at', '>=', $today)->count(),
            'topCustomers' => Customer::orderByDesc('points')->take(5)->get(),
            'recentSales' => Sale::with(['customer', 'user'])->latest()->take(6)->get(),
            'cashierSales' => (float) $cashierSummary->sales_total,
            'cashierSalesCount' => (int) $cashierSummary->sales_count,
            'chart' => $chart,
            'maxChartValue' => $maxChartValue,
            'payments' => collect([
                ['label' => 'Espèces', 'value' => (float) $todaySummary->cash_total, 'color' => 'emerald'],
                ['label' => 'Mobile Money', 'value' => (float) $todaySummary->mobile_total, 'color' => 'violet'],
                ['label' => 'Points fidélité', 'value' => (float) $todaySummary->points_total, 'color' => 'amber'],
            ]),
        ])->layout('layouts.app');
    }
}
