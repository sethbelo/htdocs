<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Livewire\Concerns\ScopesToSite;
use App\Models\Order;
use App\Services\Approvals\ApprovalService;
use App\Services\Reports\DashboardMetrics;
use App\Services\Support\Money;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tableau de bord')]
class Dashboard extends Component
{
    use ScopesToSite;

    public function render(ApprovalService $approvals)
    {
        $metrics = new DashboardMetrics(auth()->user(), $this->siteId);
        $series = $metrics->dailySeries();
        $byStatus = $metrics->ordersByStatus();
        $bySite = $metrics->revenueBySite();
        $margin = $metrics->shopMargin();

        return view('livewire.dashboard', [
            'kpis' => $metrics->kpis(),
            'lowStock' => $metrics->lowStock(),
            'urgentOrders' => Order::query()->visibleTo(auth()->user())->forSite($this->siteId)->with('client')
                ->whereNotIn('status', [OrderStatus::Closed, OrderStatus::Cancelled, OrderStatus::Delivered, OrderStatus::Draft])
                ->byUrgency()->limit(8)->get(),
            'pendingApprovals' => auth()->user()->can('approvals.view') ? $approvals->pendingFor(auth()->user())->count() : 0,
            'margin' => $margin,
            'currency' => Money::currency(),
            'revenueChart' => [
                'type' => 'line',
                'data' => [
                    'labels' => $series['labels'],
                    'datasets' => [
                        ['label' => 'Encaissements', 'data' => $series['income'], 'borderColor' => '#134A63', 'backgroundColor' => 'rgba(19,74,99,.12)', 'fill' => true, 'tension' => .35],
                        ['label' => 'Ventes boutique', 'data' => $series['shop'], 'borderColor' => '#53BDC5', 'tension' => .35],
                        ['label' => 'Dépenses', 'data' => $series['expenses'], 'borderColor' => '#DB0D17', 'borderDash' => [4, 4], 'tension' => .35],
                    ],
                ],
                'options' => ['maintainAspectRatio' => false, 'interaction' => ['mode' => 'index', 'intersect' => false], 'plugins' => ['legend' => ['position' => 'bottom']]],
            ],
            'statusChart' => [
                'type' => 'doughnut',
                'data' => ['labels' => array_keys($byStatus), 'datasets' => [['data' => array_values($byStatus), 'backgroundColor' => ['#f59e0b', '#84cc16', '#a855f7', '#8b5cf6', '#53BDC5', '#134A63', '#0ea5e9', '#10b981', '#14b8a6', '#ef4444', '#71717a']]]],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'right']], 'cutout' => '65%'],
            ],
            'siteChart' => [
                'type' => 'bar',
                'data' => ['labels' => array_keys($bySite), 'datasets' => [['label' => 'CA du mois', 'data' => array_values($bySite), 'backgroundColor' => '#53BDC5', 'borderRadius' => 6]]],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
            ],
            'mapConfig' => [
                'token' => config('erp.mapbox.token'),
                'style' => config('erp.mapbox.style'),
                'center' => config('erp.mapbox.center'),
                'points' => $metrics->mapPoints(),
            ],
        ]);
    }
}
