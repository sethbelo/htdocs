<?php

namespace App\Livewire;

use App\Models\AttendanceEntry;
use App\Models\Document;
use App\Models\Employee;
use App\Models\FinancialAccount;
use App\Models\ManufacturingOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Site;
use App\Support\Approvals;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render()
    {
        $cid = company_id();
        $month = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        // ── Indicateurs financiers ───────────────────────────────────────────
        $revenue = fn (Carbon $from, Carbon $to) => (float) Document::where('company_id', $cid)->where('type', 'invoice')->whereNotIn('status', ['cancelled'])->whereBetween('issue_date', [$from, $to])->sum('base_total');
        $revenueMonth = $revenue($month, now()->endOfMonth());
        $revenuePrev = $revenue($lastMonth, $lastMonth->copy()->endOfMonth());
        $collected = (float) Payment::where('company_id', $cid)->where('direction', 'in')->where('paid_at', '>=', $month)->sum(DB::raw('amount * exchange_rate'));
        $treasury = FinancialAccount::where('company_id', $cid)->where('is_active', true)->get()->sum(fn ($a) => $a->balance);
        $receivables = (float) Document::where('company_id', $cid)->where('type', 'invoice')->whereNotIn('status', ['paid', 'cancelled', 'draft'])->sum(DB::raw('total - amount_paid'));

        // ── Opérations ───────────────────────────────────────────────────────
        $openOrders = SalesOrder::where('company_id', $cid)->whereNotIn('stage', ['cloture', 'annule']);
        $stageCounts = SalesOrder::where('company_id', $cid)->whereNotIn('stage', ['cloture', 'annule'])->selectRaw('stage, count(*) as c, sum(total) as t')->groupBy('stage')->get()->keyBy('stage');

        // ── Graphique 6 mois : facturé / encaissé / dépenses ─────────────────
        $months = collect(range(5, 0))->map(fn ($n) => now()->subMonthsNoOverflow($n)->startOfMonth());
        $chart = [
            'type' => 'bar',
            'money' => true,
            'legend' => true,
            'labels' => $months->map(fn ($m) => ucfirst($m->translatedFormat('M')))->values(),
            'datasets' => [
                ['label' => 'Facturé', 'data' => $months->map(fn ($m) => round($revenue($m, $m->copy()->endOfMonth())))->values(), 'color' => '#104a63'],
                ['label' => 'Encaissé', 'data' => $months->map(fn ($m) => round((float) Payment::where('company_id', $cid)->where('direction', 'in')->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum(DB::raw('amount * exchange_rate'))))->values(), 'color' => '#3acece'],
                ['label' => 'Dépenses', 'data' => $months->map(fn ($m) => round((float) DB::table('expenses')->where('company_id', $cid)->whereNull('deleted_at')->whereBetween('expense_date', [$m, $m->copy()->endOfMonth()])->sum(DB::raw('amount * exchange_rate'))))->values(), 'color' => '#ffce31'],
            ],
        ];

        $presentToday = AttendanceEntry::whereHas('sheet', fn ($q) => $q->where('company_id', $cid)->whereDate('work_date', today()))->whereIn('status', ['present', 'late', 'half'])->count();

        return view('livewire.dashboard', [
            'kpis' => [
                'revenue' => $revenueMonth,
                'revenueTrend' => $revenuePrev > 0 ? ($revenueMonth - $revenuePrev) / $revenuePrev * 100 : null,
                'collected' => $collected,
                'treasury' => $treasury,
                'receivables' => $receivables,
                'openOrders' => (clone $openOrders)->count(),
                'openOrdersValue' => (float) (clone $openOrders)->sum('total'),
                'sites' => Site::where('company_id', $cid)->where('status', 'in_progress')->count(),
                'production' => ManufacturingOrder::where('company_id', $cid)->whereIn('status', ['confirmed', 'in_progress', 'quality'])->count(),
                'lowStock' => Product::where('company_id', $cid)->where('track_stock', true)->where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->count(),
                'employees' => Employee::where('company_id', $cid)->where('status', 'active')->count(),
                'presentToday' => $presentToday,
            ],
            'chart' => $chart,
            'stageCounts' => $stageCounts,
            'approvals' => Approvals::for(auth()->user()),
            'recentOrders' => SalesOrder::with('contact')->where('company_id', $cid)->latest('updated_at')->limit(7)->get(),
            'activeSites' => Site::with('manager')->where('company_id', $cid)->whereIn('status', ['in_progress', 'planned'])->orderByDesc('progress')->limit(5)->get(),
            'lowStockItems' => Product::where('company_id', $cid)->where('track_stock', true)->where('reorder_level', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_level')->orderBy('stock_quantity')->limit(5)->get(),
        ]);
    }
}
