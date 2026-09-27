<div class="dashboard-shell" wire:poll.120s="refreshDashboard">
    <div wire:loading.flex wire:target="period,refreshDashboard" class="fixed inset-0 items-center justify-center bg-base-100/45 backdrop-blur-[1px]" style="z-index:60">
        <div class="flex items-center gap-3 rounded-2xl border border-base-300 bg-base-100 px-5 py-3 font-bold shadow-xl"><span class="loading loading-spinner loading-sm text-primary"></span>Actualisation des données…</div>
    </div>

    <div class="dashboard-theme-art" aria-hidden="true">
        <svg class="dashboard-theme-art-light" viewBox="0 0 1200 720" fill="none" preserveAspectRatio="xMidYMin slice">
            <path d="M-40 126C145 36 268 215 448 118S735 44 878 145s257 54 378-25" stroke="currentColor" stroke-width="2" stroke-dasharray="8 14" />
            <path d="M38 590c138-104 263 38 405-27s246-36 352 35 235 16 376-72" stroke="currentColor" stroke-width="1.5" />
            <circle cx="1050" cy="174" r="92" stroke="currentColor" stroke-width="2" />
            <circle cx="1050" cy="174" r="126" stroke="currentColor" stroke-width="1" stroke-dasharray="5 12" />
            <path d="M116 306h180M206 216v180" stroke="currentColor" stroke-width="1" stroke-dasharray="4 10" />
        </svg>
        <svg class="dashboard-theme-art-dark" viewBox="0 0 1200 720" fill="none" preserveAspectRatio="xMidYMin slice">
            <path d="M-60 180C120 18 310 236 488 105s338 95 502-18 251 49 292 96" stroke="currentColor" stroke-width="2" />
            <path d="M36 612c158-178 316 20 462-92s289 91 430-18 248 14 336 97" stroke="currentColor" stroke-width="1.5" stroke-dasharray="7 16" />
            <path d="M1025 93l92 53v106l-92 53-92-53V146zM1025 125l64 37v74l-64 37-64-37v-74z" stroke="currentColor" stroke-width="1.5" />
            <circle cx="174" cy="318" r="5" fill="currentColor" /><circle cx="378" cy="128" r="4" fill="currentColor" /><circle cx="741" cy="562" r="5" fill="currentColor" />
            <path d="M174 318l204-190 363 434 284-363" stroke="currentColor" stroke-width="1" stroke-dasharray="3 13" />
        </svg>
    </div>

    <header class="yeboox-page-head !mb-0">
        <div class="flex min-w-0 items-center gap-4">
            <div class="yeboox-icon-tile !size-12 !rounded-2xl" style="--tone:#2563eb"><flux:icon.squares-2x2 class="size-6" /></div>
            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h1 class="text-2xl font-black tracking-tight text-base-content">Tableau de bord</h1><span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400"><span class="size-1.5 rounded-full bg-emerald-500"></span>En direct</span></div><p class="mt-1 truncate text-sm text-base-content/50">Bienvenue, {{ auth()->user()->name }} · {{ now()->translatedFormat('l d F Y') }}</p></div>
        </div>
        <div class="flex max-w-full items-center gap-2">
            <div class="scrollbar-hide flex max-w-full items-center gap-1 overflow-x-auto rounded-xl border border-base-300 bg-base-100 p-1 shadow-sm" aria-label="Période du tableau de bord">
                @foreach(['today'=>'Aujourd’hui','week'=>'Semaine','month'=>'Mois','year'=>'Année'] as $periodValue => $periodName)
                    <button type="button" wire:click="$set('period', '{{ $periodValue }}')" class="btn btn-sm shrink-0 rounded-lg border-0 px-3 {{ $period === $periodValue ? 'bg-primary text-primary-content shadow-sm hover:bg-primary' : 'bg-transparent text-base-content/55 hover:bg-base-200 hover:text-base-content' }}">{{ $periodName }}</button>
                @endforeach
            </div>
            <button type="button" wire:click="refreshDashboard" class="btn btn-sm btn-square shrink-0 rounded-xl border-base-300 bg-base-100 shadow-sm hover:border-primary hover:text-primary" title="Actualiser les données" aria-label="Actualiser les données"><flux:icon.arrow-path class="size-4" /></button>
        </div>
    </header>

    <section class="dashboard-surface dashboard-highlight relative overflow-hidden p-4">
        <svg aria-hidden="true" class="pointer-events-none absolute -right-5 -top-12 h-40 w-64 text-blue-500/10" viewBox="0 0 260 150" fill="none"><path d="M3 126C35 120 52 100 76 105s38 20 60 3 31-64 57-58 34 35 64 7" stroke="currentColor" stroke-width="9" stroke-linecap="round"/><circle cx="194" cy="50" r="29" stroke="currentColor" stroke-width="2" stroke-dasharray="5 7"/></svg>
        <div class="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-500/20"><flux:icon.signal class="size-5" /></span><div><p class="text-sm font-black">Aperçu {{ mb_strtolower($periodLabel) }}</p><p class="text-xs text-base-content/50">Données synchronisées à {{ now()->format('H:i') }} · actualisation automatique toutes les 2 minutes</p></div></div><div class="flex items-center gap-2 self-start rounded-xl border border-base-300 bg-base-100/80 px-3 py-2 text-xs shadow-sm backdrop-blur-sm sm:self-auto"><span class="size-2 rounded-full bg-success shadow-[0_0_8px_currentColor]"></span><strong>{{ $selectedOrdersCount }}</strong> vente(s) payée(s)</div></div>
    </section>

    @php
        $kpis = [
            ['label' => 'Chiffre d’affaires', 'value' => number_format($selectedRevenue, 0, ',', '.').' FC', 'change' => $revenueChange, 'icon' => 'banknotes', 'tone' => '#10b981', 'href' => route('admin.reports.dashboard')],
            ['label' => 'Commandes payées', 'value' => number_format($selectedOrdersCount, 0, ',', '.'), 'change' => $ordersChange, 'icon' => 'shopping-bag', 'tone' => '#8b5cf6', 'href' => route('admin.orders.index')],
            ['label' => 'Panier moyen', 'value' => number_format($averageBasket, 0, ',', '.').' FC', 'change' => $basketChange, 'icon' => 'receipt-percent', 'tone' => '#f59e0b', 'href' => route('admin.reports.dashboard')],
            ['label' => 'Sessions ouvertes', 'value' => number_format($openCashSessionsCount, 0, ',', '.'), 'change' => null, 'icon' => 'lock-open', 'tone' => '#3b82f6', 'href' => route('admin.cash-sessions.index')],
        ];
    @endphp
    <section class="dashboard-grid grid sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" wire:navigate class="yeboox-card-tone group block p-5 transition hover:-translate-y-0.5" style="--tone:{{ $kpi['tone'] }}">
                <svg aria-hidden="true" class="pointer-events-none absolute -right-5 -top-6 size-28 opacity-[.08]" style="color:{{ $kpi['tone'] }}" viewBox="0 0 100 100" fill="none"><circle cx="50" cy="50" r="34" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="21" stroke="currentColor" stroke-width="8" opacity=".6"/></svg>
                <div class="relative flex items-start justify-between gap-3"><div><p class="text-[10px] font-black uppercase tracking-[.12em] text-base-content/45">{{ $kpi['label'] }}</p><p class="mt-2 text-2xl font-black tracking-tight text-base-content">{{ $kpi['value'] }}</p></div><span class="yeboox-icon-tile transition group-hover:rotate-3 group-hover:scale-110" style="--tone:{{ $kpi['tone'] }}"><flux:icon :name="$kpi['icon']" class="size-5" /></span></div>
                @if($kpi['change'] !== null)
                    <div class="relative mt-4 flex items-center justify-between gap-2 text-xs"><span class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-black {{ $kpi['change'] >= 0 ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}"><flux:icon :name="$kpi['change'] >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-3.5" />{{ $kpi['change'] > 0 ? '+' : '' }}{{ number_format($kpi['change'], 1, ',', ' ') }}%</span><span class="text-base-content/40">vs précédente</span></div>
                @else
                    <span class="relative mt-4 inline-flex items-center gap-1 text-xs font-bold" style="color:{{ $kpi['tone'] }}">Voir les sessions <flux:icon.arrow-right class="size-3.5" /></span>
                @endif
            </a>
        @endforeach
    </section>

    @php
        $summaries = [
            ['label'=>'Produits','value'=>$totalProducts,'icon'=>'cube','tone'=>'text-blue-600 bg-blue-500/10','route'=>'admin.products.index'],
            ['label'=>'Catégories','value'=>$totalCategories,'icon'=>'tag','tone'=>'text-violet-600 bg-violet-500/10','route'=>'admin.categories.index'],
            ['label'=>'Clients','value'=>$totalCustomers,'icon'=>'users','tone'=>'text-cyan-600 bg-cyan-500/10','route'=>'admin.customers.index'],
            ['label'=>'Utilisateurs','value'=>$activeUsers,'icon'=>'user-group','tone'=>'text-indigo-600 bg-indigo-500/10','route'=>'admin.users.index'],
            ['label'=>'Stock faible','value'=>$lowStockCount,'icon'=>'exclamation-triangle','tone'=>'text-amber-600 bg-amber-500/10','route'=>'admin.stocks.index'],
            ['label'=>'Ruptures','value'=>$outOfStockCount,'icon'=>'archive-box-x-mark','tone'=>'text-red-600 bg-red-500/10','route'=>'admin.stocks.index'],
        ];
    @endphp
    <section class="dashboard-grid dashboard-grid-compact grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6">
        @foreach($summaries as $summary)
            <a href="{{ route($summary['route']) }}" wire:navigate class="yeboox-stat group flex !p-3.5 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $summary['tone'] }} transition group-hover:scale-110"><flux:icon :name="$summary['icon']" class="size-5" /></span><span class="min-w-0"><strong class="block text-xl font-black">{{ $summary['value'] }}</strong><span class="block truncate text-xs text-base-content/55">{{ $summary['label'] }}</span></span></a>
        @endforeach
    </section>

    <section class="dashboard-grid grid xl:grid-cols-3">
        <article class="dashboard-surface p-5 xl:col-span-2">
            <div class="flex items-start justify-between gap-3"><div><h2 class="flex items-center gap-2 font-black"><span class="grid size-9 place-items-center rounded-xl bg-primary/10 text-primary"><flux:icon.chart-bar class="size-5" /></span>Évolution des ventes</h2><p class="mt-1 text-xs text-base-content/50">Revenus payés · {{ $periodLabel }}</p></div><a href="{{ route('admin.reports.dashboard') }}" wire:navigate class="btn btn-sm rounded-xl border-base-300 bg-base-100">Rapport détaillé <flux:icon.arrow-up-right class="size-4" /></a></div>
            <div class="mt-6 flex h-64 items-end gap-1.5 border-b border-base-300 px-1 sm:gap-3">
                @foreach($salesChart as $point)
                    <div class="group flex h-full min-w-0 flex-1 flex-col justify-end text-center">
                        <div class="relative mx-auto w-full max-w-12 rounded-t-lg bg-gradient-to-t from-primary to-cyan-400 shadow-sm transition-all duration-300 group-hover:from-indigo-600 group-hover:to-cyan-300" style="height: {{ $point['height'] }}%">
                            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-lg bg-neutral px-2 py-1 text-[10px] font-bold text-neutral-content shadow-lg group-hover:block">{{ number_format($point['value'], 0, ',', '.') }} FC</div>
                        </div>
                        <span class="mt-2 truncate text-[10px] font-bold text-base-content/45 sm:text-xs">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs"><span class="text-base-content/50">Total de la période</span><strong class="text-base text-primary">{{ number_format($selectedRevenue, 0, ',', '.') }} FC</strong></div>
        </article>

        <article class="dashboard-surface p-5">
            <div class="flex items-center justify-between"><div><h2 class="font-black">Produits populaires</h2><p class="text-xs text-base-content/50">Selon les quantités vendues</p></div><span class="grid size-9 place-items-center rounded-xl bg-amber-500/10 text-amber-600"><flux:icon.trophy class="size-5" /></span></div>
            <div class="mt-4 space-y-2">
                @forelse($topProducts as $product)
                    <div class="flex items-center gap-3 rounded-xl border border-transparent p-2.5 transition hover:border-base-300 hover:bg-base-200/60"><span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $loop->first ? 'bg-amber-500 text-white' : 'bg-base-200 text-base-content/60' }} text-xs font-black">{{ $loop->iteration }}</span><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $product['name'] }}</p><p class="text-[11px] text-base-content/45">{{ number_format($product['quantity'], 0, ',', '.') }} unité(s)</p></div><strong class="whitespace-nowrap text-xs">{{ number_format($product['revenue'], 0, ',', '.') }} FC</strong></div>
                @empty
                    <div class="grid min-h-48 place-items-center text-center text-base-content/40"><div><flux:icon.chart-bar class="mx-auto size-10" /><p class="mt-2 text-sm">Aucune vente sur cette période</p></div></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-grid grid lg:grid-cols-2">
        <article class="dashboard-surface overflow-hidden">
            <div class="dashboard-panel-head flex items-center justify-between p-5"><div><h2 class="flex items-center gap-2 font-black"><flux:icon.shopping-cart class="size-5 text-violet-600" />Commandes récentes</h2><p class="text-xs text-base-content/50">{{ $activeOrdersCount }} commande(s) active(s)</p></div><a href="{{ route('admin.orders.index') }}" wire:navigate class="btn btn-sm btn-ghost text-primary">Tout voir <flux:icon.arrow-right class="size-4" /></a></div>
            <div class="divide-y divide-base-300">
                @forelse($recentOrders as $order)
                    @php $statusClass = match($order['status']) {'completed'=>'badge-success','cancelled'=>'badge-error','preparing'=>'badge-warning','ready'=>'badge-info',default=>'badge-primary'}; @endphp
                    <a href="{{ route('admin.orders.index') }}" wire:navigate class="flex items-center gap-3 p-4 transition hover:bg-base-200/60"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-500/10 text-violet-600"><flux:icon.receipt-percent class="size-5" /></span><div class="min-w-0 flex-1"><div class="flex items-center gap-2"><strong class="truncate text-sm">{{ $order['number'] }}</strong><span class="badge badge-sm {{ $statusClass }}">{{ $order['status_label'] }}</span></div><p class="truncate text-xs text-base-content/50">{{ $order['customer'] }} · {{ $order['time'] }}</p></div><strong class="whitespace-nowrap text-sm">{{ number_format($order['total'], 0, ',', '.') }} FC</strong></a>
                @empty
                    <div class="p-10 text-center text-sm text-base-content/45">Aucune commande enregistrée.</div>
                @endforelse
            </div>
        </article>

        <article class="dashboard-surface overflow-hidden">
            <div class="dashboard-panel-head flex items-center justify-between p-5"><div><h2 class="flex items-center gap-2 font-black"><flux:icon.archive-box class="size-5 text-amber-600" />Alertes de stock</h2><p class="text-xs text-base-content/50">Articles à réapprovisionner</p></div><a href="{{ route('admin.stocks.index', ['stockFilter'=>'low']) }}" wire:navigate class="btn btn-sm btn-ghost text-primary">Inventaire <flux:icon.arrow-right class="size-4" /></a></div>
            <div class="divide-y divide-base-300">
                @forelse($lowStockItems as $stock)
                    <a href="{{ route('admin.stocks.index') }}" wire:navigate class="flex items-center gap-3 p-4 transition hover:bg-base-200/60"><span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $stock['out'] ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning-content' }}"><flux:icon :name="$stock['out'] ? 'x-circle' : 'exclamation-triangle'" class="size-5" /></span><div class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ $stock['name'] }}</strong><p class="truncate text-xs text-base-content/50">{{ $stock['warehouse'] }} · Seuil {{ number_format($stock['alert'], 0, ',', '.') }}</p></div><span class="badge {{ $stock['out'] ? 'badge-error' : 'badge-warning' }}">{{ $stock['out'] ? 'Rupture' : number_format($stock['quantity'], 0, ',', '.') }}</span></a>
                @empty
                    <div class="grid min-h-56 place-items-center p-8 text-center"><div class="text-success"><flux:icon.check-circle class="mx-auto size-10" /><p class="mt-2 text-sm font-bold">Aucune alerte de stock</p></div></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-surface p-5">
        <div class="mb-4 flex items-center justify-between"><div><h2 class="font-black">Actions rapides</h2><p class="text-xs text-base-content/50">Accédez directement aux opérations courantes.</p></div><flux:icon.bolt class="size-6 text-amber-500" /></div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @can('pos.access')<a href="{{ route('pos.terminal') }}" class="dashboard-quick-action group bg-emerald-500/5 shadow-emerald-900/10 hover:bg-emerald-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20"><flux:icon.computer-desktop class="size-6" /></span><span class="mt-3 block text-xs font-black">Ouvrir le PDV</span></a>@endcan
            @can('products.create')<a href="{{ route('admin.products.create') }}" wire:navigate class="dashboard-quick-action group bg-blue-500/5 shadow-blue-900/10 hover:bg-blue-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-blue-500 text-white shadow-lg shadow-blue-500/20"><flux:icon.plus class="size-6" /></span><span class="mt-3 block text-xs font-black">Nouveau produit</span></a>@endcan
            @can('customers.create')<a href="{{ route('admin.customers.create') }}" wire:navigate class="dashboard-quick-action group bg-violet-500/5 shadow-violet-900/10 hover:bg-violet-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-violet-500 text-white shadow-lg shadow-violet-500/20"><flux:icon.user-plus class="size-6" /></span><span class="mt-3 block text-xs font-black">Nouveau client</span></a>@endcan
            @can('reports.view')<a href="{{ route('admin.reports.dashboard') }}" wire:navigate class="dashboard-quick-action group bg-amber-500/5 shadow-amber-900/10 hover:bg-amber-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-amber-500 text-white shadow-lg shadow-amber-500/20"><flux:icon.document-chart-bar class="size-6" /></span><span class="mt-3 block text-xs font-black">Rapports</span></a>@endcan
            @can('stocks.view')<a href="{{ route('admin.stocks.index') }}" wire:navigate class="dashboard-quick-action group bg-cyan-500/5 shadow-cyan-900/10 hover:bg-cyan-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-cyan-500 text-white shadow-lg shadow-cyan-500/20"><flux:icon.archive-box class="size-6" /></span><span class="mt-3 block text-xs font-black">Inventaire</span></a>@endcan
            @can('settings.view')<a href="{{ route('admin.settings.index') }}" wire:navigate class="dashboard-quick-action group bg-slate-500/5 shadow-slate-900/10 hover:bg-slate-500/10"><span class="mx-auto grid size-11 place-items-center rounded-xl bg-slate-600 text-white shadow-lg shadow-slate-500/20"><flux:icon.cog-6-tooth class="size-6" /></span><span class="mt-3 block text-xs font-black">Paramètres</span></a>@endcan
        </div>
    </section>
</div>
