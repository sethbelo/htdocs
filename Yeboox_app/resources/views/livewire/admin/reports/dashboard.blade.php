<div class="p-6 space-y-6" wire:key="reports-{{ $period }}">
    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Rapports & Analytique</h1>
            <p class="text-sm text-slate-500 mt-1">Tableau de bord financier et performance commerciale</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="period" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm focus:ring-violet-500">
                <option value="today">Aujourd'hui</option>
                <option value="yesterday">Hier</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="quarter">Ce trimestre</option>
                <option value="year">Cette année</option>
                <option value="custom">Personnalisé</option>
            </select>
            @if($period === 'custom')
                <input wire:model.live="dateFrom" type="date" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
                <input wire:model.live="dateTo" type="date" class="rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm">
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        @php
            $kpis = [
                ['label' => 'Chiffre d\'affaires', 'value' => number_format($totalRevenue, 0, ',', '.') . ' FC', 'color' => 'emerald', 'icon' => 'currency-dollar'],
                ['label' => 'Commandes', 'value' => $orderCount, 'color' => 'blue', 'icon' => 'shopping-cart'],
                ['label' => 'Ticket moyen', 'value' => number_format($avgTicket, 0, ',', '.') . ' FC', 'color' => 'violet', 'icon' => 'receipt-percent'],
                ['label' => 'Payées', 'value' => $paidOrders, 'color' => 'teal', 'icon' => 'check-badge'],
                ['label' => 'Dépenses', 'value' => number_format($totalExpenses, 0, ',', '.') . ' FC', 'color' => 'red', 'icon' => 'arrow-trending-down'],
                ['label' => 'Bénéfice net', 'value' => number_format($profit, 0, ',', '.') . ' FC', 'color' => $profit >= 0 ? 'green' : 'red', 'icon' => 'chart-bar'],
                ['label' => 'Clients', 'value' => $customerCount, 'color' => 'amber', 'icon' => 'users'],
            ];
        @endphp
        @foreach($kpis as $kpi)
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg bg-{{ $kpi['color'] }}-100 dark:bg-{{ $kpi['color'] }}-500/20 flex items-center justify-center">
                        <x-dynamic-component :component="'heroicon-o-' . $kpi['icon']" class="w-4 h-4 text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400"/>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider">{{ $kpi['label'] }}</p>
                <p class="text-lg font-bold text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400 mt-0.5">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Charts Row 1: Revenue + Payment Methods --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Revenue Chart (2/3 width) --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">📈 Évolution du chiffre d'affaires</h3>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Payment Methods (1/3 width) --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">💳 Modes de paiement</h3>
            <div class="h-48 flex items-center justify-center">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($revenueByMethod as $method)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 dark:text-slate-400">{{ $method->name }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($method->total, 0, ',', '.') }} FC</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Charts Row 2: Hourly + Order Types --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Hourly Revenue --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">🕐 Revenus par heure</h3>
            <div class="h-56">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>

        {{-- Order Types --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">🛒 Types de commande</h3>
            @if($revenueByType->isNotEmpty())
                <div class="space-y-3 mt-2">
                    @php
                        $maxRevByType = $revenueByType->max('revenue') ?: 1;
                        $typeLabels = [
                            'dine_in' => ['🍽️ Sur place', 'violet'],
                            'takeaway' => ['🥡 Emporter', 'amber'],
                            'delivery' => ['🚚 Livraison', 'blue'],
                            'drive' => ['🚗 Drive', 'emerald'],
                        ];
                    @endphp
                    @foreach($revenueByType as $type)
                        @php
                            $typeKey = is_string($type->type) ? $type->type : $type->type->value;
                            $info = $typeLabels[$typeKey] ?? ['📦 Autre', 'slate'];
                            $pct = ($type->revenue / $maxRevByType) * 100;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-slate-700 dark:text-slate-300">{{ $info[0] }}</span>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ number_format($type->revenue, 0, ',', '.') }} FC</span>
                                    <span class="text-xs text-slate-400 ml-1">({{ $type->count }})</span>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5">
                                <div class="bg-{{ $info[1] }}-500 rounded-full h-2.5 transition-all duration-700" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-slate-400 text-sm">Aucune donnée</div>
            @endif
        </div>
    </div>

    {{-- Row 3: Top Products + Expense Categories --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">🏆 Top 10 Produits</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <th class="px-5 py-2 text-left w-8">#</th>
                        <th class="px-5 py-2 text-left">Produit</th>
                        <th class="px-5 py-2 text-right">Qté</th>
                        <th class="px-5 py-2 text-right">CA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @foreach($topProducts as $i => $product)
                        @php
                            $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => $i + 1 };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-5 py-2.5 text-center">{{ $medal }}</td>
                            <td class="px-5 py-2.5 text-slate-900 dark:text-white font-medium">{{ $product->name }}</td>
                            <td class="px-5 py-2.5 text-right font-mono text-slate-600 dark:text-slate-400">{{ (int)$product->qty }}</td>
                            <td class="px-5 py-2.5 text-right font-mono font-bold text-emerald-600">{{ number_format($product->revenue, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($topProducts->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm">Aucune vente sur cette période</div>
            @endif
        </div>

        {{-- Expense Breakdown --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">📊 Répartition des dépenses</h3>
            </div>
            @if($expensesByCategory->isNotEmpty())
                <div class="p-5">
                    <div class="h-48 flex items-center justify-center">
                        <canvas id="expensesChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach($expensesByCategory as $cat)
                            @php $catLabel = \App\Models\Expense::categories()[$cat->category] ?? $cat->category; @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">{{ $catLabel }}</span>
                                <span class="font-bold text-red-600">{{ number_format($cat->total, 0, ',', '.') }} FC</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-12 text-slate-400 text-sm">Aucune dépense sur cette période</div>
            @endif
        </div>
    </div>

    {{-- Row 4: Staff Performance + Recent Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Staff Performance --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">👨‍💼 Performance du personnel</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <th class="px-5 py-2 text-left">Employé</th>
                        <th class="px-5 py-2 text-right">Commandes</th>
                        <th class="px-5 py-2 text-right">CA Généré</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @foreach($staffPerformance as $staff)
                        <tr>
                            <td class="px-5 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($staff->name, 0, 2)) }}
                                    </div>
                                    <span class="text-slate-900 dark:text-white font-medium">{{ $staff->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-2.5 text-right font-mono text-slate-600 dark:text-slate-400">{{ $staff->order_count }}</td>
                            <td class="px-5 py-2.5 text-right font-mono font-bold text-emerald-600">{{ number_format($staff->revenue, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($staffPerformance->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm">Aucune donnée</div>
            @endif
        </div>

        {{-- Recent Orders --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">🕒 Dernières commandes</h3>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-700/50">
                @foreach($recentOrders as $order)
                    @php
                        $statusMap = [
                            'pending' => ['En attente', 'amber'],
                            'confirmed' => ['Confirmée', 'blue'],
                            'preparing' => ['En prépa', 'orange'],
                            'ready' => ['Prête', 'emerald'],
                            'served' => ['Servie', 'teal'],
                            'completed' => ['Terminée', 'green'],
                            'cancelled' => ['Annulée', 'red'],
                        ];
                        $st = $statusMap[$order->status->value] ?? ['—', 'slate'];
                    @endphp
                    <div class="px-5 py-2.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div>
                            <span class="text-xs font-mono font-bold text-violet-600 dark:text-violet-400">{{ $order->order_number }}</span>
                            <span class="text-xs text-slate-400 ml-2">{{ $order->created_at->format('H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $st[1] }}-100 dark:bg-{{ $st[1] }}-500/20 text-{{ $st[1] }}-600 dark:text-{{ $st[1] }}-400">{{ $st[0] }}</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($order->total, 0, ',', '.') }} FC</span>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($recentOrders->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm">Aucune commande</div>
            @endif
        </div>
    </div>
</div>

{{-- Chart.js CDN --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
@endpush

{{-- Inline Chart Script — re-renders with Livewire because it's inside the component --}}
<script>
document.addEventListener('livewire:navigated', () => initReportCharts());
document.addEventListener('livewire:morph', () => {
    setTimeout(() => initReportCharts(), 100);
});

function initReportCharts() {
    if (typeof Chart === 'undefined') return;

    // Destroy existing charts
    ['revenueChart', 'paymentMethodChart', 'hourlyChart', 'expensesChart'].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) {
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();
        }
    });

    // Revenue by Day
    const revCtx = document.getElementById('revenueChart');
    if (revCtx && window.__reportData?.revenueByDay) {
        const data = window.__reportData.revenueByDay;
        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: data.map(d => {
                    const date = new Date(d.day);
                    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
                }),
                datasets: [{
                    label: 'CA (FC)',
                    data: data.map(d => d.revenue),
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 2,
                    borderRadius: 8,
                    barPercentage: 0.6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => (v/1000) + 'K', color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
                }
            }
        });
    }

    // Payment Methods
    const payCtx = document.getElementById('paymentMethodChart');
    if (payCtx && window.__reportData?.revenueByMethod) {
        const data = window.__reportData.revenueByMethod;
        const colors = ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
        new Chart(payCtx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.name),
                datasets: [{ data: data.map(d => d.total), backgroundColor: colors.slice(0, data.length), borderWidth: 0, hoverOffset: 8 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '65%' }
        });
    }

    // Hourly Revenue
    const hourCtx = document.getElementById('hourlyChart');
    if (hourCtx && window.__reportData?.revenueByHour) {
        const data = window.__reportData.revenueByHour;
        const hours = Array.from({length: 24}, (_, i) => i);
        const hourData = hours.map(h => { const f = data.find(d => d.hour === h); return f ? f.revenue : 0; });
        new Chart(hourCtx, {
            type: 'line',
            data: {
                labels: hours.map(h => h + 'h'),
                datasets: [{ label: 'CA', data: hourData, borderColor: 'rgb(139, 92, 246)', backgroundColor: 'rgba(139, 92, 246, 0.1)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHitRadius: 10 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => (v/1000) + 'K', color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                    x: { ticks: { color: '#94a3b8', maxTicksLimit: 12 }, grid: { display: false } }
                }
            }
        });
    }

    // Expenses
    const expCtx = document.getElementById('expensesChart');
    if (expCtx && window.__reportData?.expensesByCategory) {
        const data = window.__reportData.expensesByCategory;
        const colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#06b6d4', '#8b5cf6', '#ec4899'];
        new Chart(expCtx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.category),
                datasets: [{ data: data.map(d => d.total), backgroundColor: colors.slice(0, data.length), borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
        });
    }
}

// Set chart data — this script re-executes on Livewire morphs because it's inline
window.__reportData = {
    revenueByDay: @json($revenueByDay),
    revenueByMethod: @json($revenueByMethod),
    revenueByHour: @json($revenueByHour),
    expensesByCategory: @json($expensesByCategory),
};
document.addEventListener('DOMContentLoaded', () => setTimeout(initReportCharts, 200));
// Also init after Livewire update
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:update', () => setTimeout(initReportCharts, 200));
}
// Fallback: init right now if Chart is already loaded
if (typeof Chart !== 'undefined') setTimeout(initReportCharts, 200);
</script>

