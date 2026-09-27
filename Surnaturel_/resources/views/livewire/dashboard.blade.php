<div class="space-y-6">
    <x-page-header title="Tableau de bord" :subtitle="now()->translatedFormat('l d F Y')">
        <x-slot:actions>
            @if (auth()->user()->accessibleSiteIds() && count(auth()->user()->accessibleSiteIds()) > 1)
                <flux:select wire:model.live="siteId" size="sm" class="min-w-52">
                    <flux:select.option value="">Tous les sièges</flux:select.option>
                    @foreach ($this->accessibleSites() as $site)
                        <flux:select.option :value="$site->id">{{ $site->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
            @can('orders.create')
                <flux:button :href="route('orders.create')" wire:navigate variant="primary" icon="plus" size="sm">Nouvelle commande</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" :heading="session('status')" />
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-7" wire:poll.120s>
        <x-kpi label="CA aujourd’hui" :value="\App\Services\Support\Money::format($kpis['revenue_today'])" icon="banknotes" color="emerald" />
        <x-kpi label="CA du mois" :value="\App\Services\Support\Money::format($kpis['revenue_month'])" icon="chart-bar" color="indigo" />
        <x-kpi label="Commandes du jour" :value="$kpis['orders_today']" icon="clipboard-document-list" color="sky" :href="route('orders.index')" />
        <x-kpi label="En attente paiement" :value="$kpis['orders_awaiting_payment']" icon="clock" color="amber" :href="route('orders.index', ['statut' => 'awaiting_payment'])" />
        <x-kpi label="En graphisme" :value="$kpis['orders_in_design']" icon="paint-brush" color="purple" :href="route('design.board')" />
        <x-kpi label="BAT à valider" :value="$kpis['proofs_pending']" icon="document-check" color="purple" :href="route('orders.index', ['statut' => 'proof_pending'])" />
        <x-kpi label="Impressions en cours" :value="$kpis['printing']" icon="printer" color="indigo" :href="route('production.board')" />
        <x-kpi label="Commandes prêtes" :value="$kpis['orders_ready']" icon="check-circle" color="emerald" :href="route('orders.index', ['statut' => 'ready'])" />
        <x-kpi label="Livraisons" :value="$kpis['deliveries']" icon="truck" color="sky" :href="route('deliveries.index')" />
        <x-kpi label="Ventes boutique (jour)" :value="\App\Services\Support\Money::format($kpis['shop_sales_today'])" icon="shopping-cart" color="emerald" />
        <x-kpi label="Dépenses du mois" :value="\App\Services\Support\Money::format($kpis['expenses_month'])" icon="arrow-trending-down" color="red" />
        <x-kpi label="Trésorerie" :value="\App\Services\Support\Money::format($kpis['treasury'])" icon="building-library" color="indigo" />
        <x-kpi label="Créances clients" :value="\App\Services\Support\Money::format($kpis['receivables'])" icon="document-currency-dollar" color="amber" :href="route('invoices.index')" />
        <x-kpi label="Stock faible" :value="$kpis['low_stock']" icon="exclamation-triangle" :color="$kpis['low_stock'] ? 'red' : 'zinc'" :href="route('inventory.index', ['alerte' => 1])" />
    </div>

    @if ($pendingApprovals)
        <flux:callout icon="check-badge" color="indigo">
            <flux:callout.heading>{{ $pendingApprovals }} demande(s) attendent votre validation</flux:callout.heading>
            <x-slot name="actions"><flux:button size="sm" :href="route('approvals.index')" wire:navigate>Ouvrir</flux:button></x-slot>
        </flux:callout>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="panel p-4 xl:col-span-2" wire:key="revenue-{{ $siteId }}">
            <flux:heading>Encaissements, ventes et dépenses — 30 jours</flux:heading>
            <div class="mt-3 h-72" x-data="chart(@js($revenueChart))"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="panel p-4" wire:key="status-{{ $siteId }}">
            <flux:heading>Commandes en cours par statut</flux:heading>
            <div class="mt-3 h-72" x-data="chart(@js($statusChart))"><canvas x-ref="canvas"></canvas></div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="panel p-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading>Commandes prioritaires</flux:heading>
                <flux:link :href="route('orders.index')" wire:navigate class="text-sm">Toutes les commandes</flux:link>
            </div>
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>N°</flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column>Délai</flux:table.column>
                    <flux:table.column>Statut</flux:table.column>
                    <flux:table.column align="end">Reste</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($urgentOrders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell>
                                <flux:link :href="route('orders.show', $order)" wire:navigate class="font-medium">{{ $order->number }}</flux:link>
                                @if ($order->priority->value === 'urgent')
                                    <flux:badge size="sm" color="red" class="ms-1">Urgent</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="max-w-48 truncate">{{ $order->client->displayName() }}</flux:table.cell>
                            <flux:table.cell @class(['text-red-600 font-medium' => $order->isOverdue()])>{{ $order->due_at?->translatedFormat('d M H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell><x-status-badge :status="$order->status" /></flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$order->balanceDue()" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5"><x-empty-state title="Aucune commande en cours" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="space-y-4">
            <div class="panel p-4">
                <flux:heading>Marge boutique du mois</flux:heading>
                <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div><dt class="text-xs text-zinc-500">CA HT</dt><dd class="font-semibold"><x-money :amount="$margin['revenue']" /></dd></div>
                    <div><dt class="text-xs text-zinc-500">Coût</dt><dd class="font-semibold"><x-money :amount="$margin['cost']" /></dd></div>
                    <div><dt class="text-xs text-zinc-500">Marge</dt><dd @class(['font-semibold', 'text-emerald-600' => $margin['margin'] >= 0, 'text-red-600' => $margin['margin'] < 0])><x-money :amount="$margin['margin']" /></dd></div>
                </dl>
            </div>
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <flux:heading>Stock faible</flux:heading>
                    <flux:link :href="route('inventory.index', ['alerte' => 1])" wire:navigate class="text-sm">Voir</flux:link>
                </div>
                <ul class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($lowStock as $level)
                        <li class="flex items-center justify-between py-2 text-sm" wire:key="ls-{{ $level->id }}">
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ $level->product->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $level->warehouse->name }}</div>
                            </div>
                            <flux:badge size="sm" color="red">{{ rtrim(rtrim(number_format((float) $level->quantity, 3, ',', ' '), '0'), ',') }} / {{ (float) $level->product->min_stock }}</flux:badge>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-zinc-500">Aucune alerte</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="panel p-4" wire:key="site-{{ $siteId }}">
            <flux:heading>Performance par siège (mois)</flux:heading>
            <div class="mt-3 h-64" x-data="chart(@js($siteChart))"><canvas x-ref="canvas"></canvas></div>
        </div>
        <div class="panel overflow-hidden xl:col-span-2">
            <div class="p-4 pb-0"><flux:heading>Implantations : sièges, boutiques, entrepôts, points de vente</flux:heading></div>
            @if ($mapConfig['token'])
                <div wire:ignore x-data="sitesMap(@js($mapConfig))" class="mt-3 h-72"><div x-ref="map" class="h-full w-full"></div></div>
            @else
                <div class="m-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($mapConfig['points'] as $point)
                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                            <span class="size-2.5 rounded-full" style="background: {{ $point['color'] }}"></span>
                            <span class="font-medium">{{ $point['name'] }}</span>
                            <span class="text-zinc-500">· {{ $point['kind'] }}</span>
                        </div>
                    @endforeach
                    <flux:text size="sm" class="sm:col-span-2">Renseignez <code>MAPBOX_TOKEN</code> dans le fichier <code>.env</code> pour afficher la carte interactive.</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
