<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header :title="'Bonjour, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" :eyebrow="ucfirst(now()->translatedFormat('l j F Y'))"
                      subtitle="Vue d’ensemble de l’activité SOGEREF : ventes, production, chantiers, stock et trésorerie.">
        <x-slot:actions>
            @if (auth()->user()->canAccessModule('vouchers'))
                <x-ui.button variant="secondary" icon="ticket" :href="route('vouchers')">Nouveau bon</x-ui.button>
            @endif
            @if (auth()->user()->canManage('quotes'))
                <x-ui.button variant="accent" icon="plus" :href="route('quotes.create')">Nouveau devis</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. INDICATEURS CLÉS ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-ui.stat label="Chiffre d’affaires du mois" :value="money($kpis['revenue'], 'USD', 0)" icon="presentation-chart-line" :trend="$kpis['revenueTrend']" hint="vs mois dernier" :href="route('invoices')" />
        <x-ui.stat label="Encaissé ce mois" :value="money($kpis['collected'], 'USD', 0)" icon="banknotes" tone="lagoon" :hint="'Créances : '.money($kpis['receivables'], 'USD', 0)" :href="route('cash')" />
        <x-ui.stat label="Commandes en cours" :value="$kpis['openOrders']" icon="arrow-path-rounded-square" tone="sun" :hint="money($kpis['openOrdersValue'], 'USD', 0).' en portefeuille'" :href="route('orders.index')" />
        <x-ui.stat label="Trésorerie disponible" :value="money($kpis['treasury'], 'USD', 0)" icon="building-library" tone="emerald" hint="Banques, caisses & mobile money" :href="route('banking')" />
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:mt-4 sm:gap-4 xl:grid-cols-4">
        <x-ui.stat label="Chantiers actifs" :value="$kpis['sites']" icon="home-modern" :href="route('sites')" />
        <x-ui.stat label="Ordres en atelier" :value="$kpis['production']" icon="wrench-screwdriver" tone="sun" :href="route('manufacturing')" />
        <x-ui.stat label="Articles sous le seuil" :value="$kpis['lowStock']" icon="exclamation-triangle" :tone="$kpis['lowStock'] ? 'flame' : 'emerald'" hint="à réapprovisionner" :href="route('purchases')" />
        <x-ui.stat label="Effectif actif" :value="$kpis['employees']" icon="user-group" tone="lagoon" :hint="$kpis['presentToday'].' présents sur chantier aujourd’hui'" :href="route('employees')" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- ═══ 3. ACTIVITÉ FINANCIÈRE ═══════════════════════════════════════ --}}
        <x-ui.card title="Activité des 6 derniers mois" subtitle="Montants en USD" icon="chart-bar" class="xl:col-span-2">
            <div class="h-72" x-data="erpChart(@js($chart))"><canvas x-ref="canvas"></canvas></div>
        </x-ui.card>

        {{-- ═══ 4. À TRAITER ═════════════════════════════════════════════════ --}}
        <x-ui.card title="À traiter" subtitle="Validations en attente" icon="bell-alert" :padded="false">
            @forelse ($approvals as $item)
                <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 border-b border-line px-5 py-3.5 transition last:border-0 hover:bg-surface-2">
                    <span class="grid size-9 place-items-center rounded-xl bg-sun-100 text-sun-800 dark:bg-sun-400/15 dark:text-sun-300"><x-ui.icon :name="$item['icon']" class="size-[18px]" /></span>
                    <span class="flex-1 text-[13.5px] font-medium">{{ $item['label'] }}</span>
                    <span class="font-display text-[18px] font-semibold text-brand-600 dark:text-lagoon-300">{{ $item['count'] }}</span>
                    <x-ui.icon name="chevron-right" class="size-4 text-fg-subtle" />
                </a>
            @empty
                <x-ui.empty icon="check-badge" title="Tout est à jour" text="Aucune validation ne vous attend." />
            @endforelse
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- ═══ 5. ENTONNOIR DES COMMANDES ═══════════════════════════════════ --}}
        <x-ui.card title="Parcours des commandes" subtitle="Du devis à la livraison" icon="funnel">
            @php $maxCount = max(1, $stageCounts->max('c') ?? 1); @endphp
            <ul class="space-y-3">
                @foreach (config('erp.statuses.order_stage') as $stage => [$label, $tone])
                    @continue(in_array($stage, ['cloture', 'annule']))
                    @php $row = $stageCounts->get($stage); @endphp
                    <li>
                        <a href="{{ route('orders.index', ['stage' => $stage]) }}" wire:navigate class="group block">
                            <div class="mb-1 flex items-baseline justify-between text-[12.5px]">
                                <span class="font-semibold text-fg group-hover:text-brand-600">{{ $label }}</span>
                                <span class="text-fg-subtle tabular-nums">{{ $row->c ?? 0 }} · {{ money($row->t ?? 0, 'USD', 0) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-white/6">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-lagoon-400" style="width: {{ ($row->c ?? 0) / $maxCount * 100 }}%"></div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        {{-- ═══ 6. COMMANDES RÉCENTES ════════════════════════════════════════ --}}
        <x-ui.card title="Commandes récentes" icon="arrow-path-rounded-square" :padded="false" class="xl:col-span-2">
            <x-slot:actions><x-ui.button variant="ghost" size="sm" :href="route('orders.index')" icon-right="arrow-right">Tout voir</x-ui.button></x-slot:actions>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Commande</th><th>Client</th><th>Nature</th><th>Étape</th><th class="num">Montant</th><th class="num">Encaissé</th></tr></thead>
                    <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="cursor-pointer" onclick="Livewire.navigate('{{ route('orders.show', $order) }}')">
                            <td><p class="font-semibold">{{ $order->number }}</p><p class="max-w-[220px] truncate text-[12px] text-fg-subtle">{{ $order->title }}</p></td>
                            <td class="max-w-[180px] truncate">{{ $order->contact?->display_name }}</td>
                            <td class="text-fg-muted">{{ config('erp.order_kinds.'.$order->kind) }}</td>
                            <td><x-ui.status map="order_stage" :value="$order->stage" /></td>
                            <td class="num font-semibold">{{ money($order->total, $order->currency_code) }}</td>
                            <td class="num"><span @class(['text-emerald-600' => $order->balanceDue() <= 0, 'text-fg-muted' => $order->balanceDue() > 0])>{{ round($order->paidRatio() * 100) }} %</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="document-text" title="Aucune commande" text="Les dossiers ouverts depuis un devis apparaîtront ici." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- ═══ 7. CHANTIERS ═════════════════════════════════════════════════ --}}
        <x-ui.card title="Chantiers en cours" icon="home-modern">
            <x-slot:actions><x-ui.button variant="ghost" size="sm" :href="route('sites')" icon-right="arrow-right">Chantiers</x-ui.button></x-slot:actions>
            <ul class="space-y-4">
                @forelse ($activeSites as $site)
                    <li>
                        <a href="{{ route('sites.show', $site) }}" wire:navigate class="flex items-center gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 font-display text-[12px] font-bold text-brand-600 dark:bg-white/8 dark:text-lagoon-300">{{ $site->progress }}%</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate font-semibold">{{ $site->name }}</p>
                                    <x-ui.status map="site" :value="$site->status" />
                                </div>
                                <p class="truncate text-[12px] text-fg-subtle">{{ $site->city }} · Resp. {{ $site->manager?->full_name ?? '—' }}@if ($site->isDelayed()) · <span class="font-semibold text-flame-600">en retard</span>@endif</p>
                                <x-ui.progress :value="$site->progress" class="mt-2" :tone="$site->isDelayed() ? 'flame' : 'brand'" />
                            </div>
                        </a>
                    </li>
                @empty
                    <li><x-ui.empty icon="home-modern" title="Aucun chantier actif" /></li>
                @endforelse
            </ul>
        </x-ui.card>

        {{-- ═══ 8. ALERTES DE STOCK ══════════════════════════════════════════ --}}
        <x-ui.card title="Alertes de stock" icon="exclamation-triangle" :padded="false">
            <x-slot:actions><x-ui.button variant="soft" size="sm" :href="route('purchases')" icon="truck">Réapprovisionner</x-ui.button></x-slot:actions>
            <ul>
                @forelse ($lowStockItems as $product)
                    <li class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                        <span class="grid size-9 place-items-center rounded-lg bg-flame-50 text-flame-600 dark:bg-flame-400/12"><x-ui.icon name="cube" class="size-[18px]" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-semibold">{{ $product->name }}</p>
                            <p class="text-[12px] text-fg-subtle">{{ $product->sku }} · seuil {{ qty($product->reorder_level) }} {{ $product->unit }}</p>
                        </div>
                        <span class="text-right"><span class="block font-display text-[16px] font-semibold text-flame-600">{{ qty($product->stock_quantity) }}</span><span class="text-[11px] text-fg-subtle">{{ $product->unit }}</span></span>
                    </li>
                @empty
                    <li><x-ui.empty icon="check-circle" title="Stock sain" text="Aucun article sous son seuil de réapprovisionnement." /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
