<div class="mx-auto w-full max-w-[1600px] space-y-6" wire:poll.60s wire:loading.class="opacity-75">
    @php($greeting = now()->hour < 12 ? 'Bonjour' : (now()->hour < 18 ? 'Bon après-midi' : 'Bonsoir'))
    {{-- En-tête partagé : résumé de l'activité et accès rapides du tableau de bord. --}}
    <x-page-hero
        :title="$greeting.', '.str($user->name)->before(' ')"
        eyebrow="Vue d’ensemble"
        :description="'Les indicateurs essentiels de '.$companyName.', actualisés automatiquement.'"
        icon="home"
        :badge-icon="$activeSession ? 'check-circle' : 'lock-closed'"
        illustration="dashboard"
    >
        <x-slot:actions>
            <flux:badge color="zinc" :icon="$activeSession ? 'check-circle' : 'lock-closed'" class="bg-white/10! text-white! ring-white/20!">Caisse {{ $activeSession ? 'ouverte' : 'fermée' }}</flux:badge>
            <flux:select wire:model.live="period" size="sm" aria-label="Période du graphique" class="w-36 bg-white! text-zinc-900!">
                <flux:select.option value="7">7 jours</flux:select.option>
                <flux:select.option value="14">14 jours</flux:select.option>
                <flux:select.option value="30">30 jours</flux:select.option>
            </flux:select>
            <flux:button wire:click="refreshDashboard" wire:loading.attr="disabled" icon="arrow-path" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!">
                <span wire:loading.remove wire:target="refreshDashboard">Actualiser</span>
                <span wire:loading wire:target="refreshDashboard">Actualisation…</span>
            </flux:button>
        </x-slot:actions>
    </x-page-hero>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicateurs principaux">
        <flux:card class="relative overflow-hidden p-5!">
            <div class="flex items-start justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                    <flux:icon.banknotes class="size-5" />
                </span>
                <flux:badge :color="$salesTrend >= 0 ? 'emerald' : 'red'" :icon="$salesTrend >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'">
                    {{ $salesTrend >= 0 ? '+' : '' }}{{ $salesTrend }}%
                </flux:badge>
            </div>
            <flux:text size="sm" class="mt-5">Ventes aujourd’hui</flux:text>
            <flux:heading class="mt-1 text-2xl! font-semibold!"><x-money :amount="$isManager ? $todaySales : $cashierSales" /></flux:heading>
            <flux:text size="sm" class="mt-1">{{ $isManager ? $todaySalesCount : $cashierSalesCount }} ticket(s) · devise {{ \App\Support\DisplayCurrency::code() }}</flux:text>
        </flux:card>

        <flux:card class="p-5!">
            <div class="flex items-start justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                    <flux:icon.calendar-days class="size-5" />
                </span>
                <flux:badge color="sky">{{ $confirmedAppointments }} confirmés</flux:badge>
            </div>
            <flux:text size="sm" class="mt-5">Rendez-vous du jour</flux:text>
            <flux:heading class="mt-1 text-2xl! font-semibold!">{{ $appointments->count() }}</flux:heading>
            <flux:link href="{{ route('appointments') }}" wire:navigate class="mt-2 inline-flex">Voir le planning</flux:link>
        </flux:card>

        <flux:card class="p-5!">
            <div class="flex items-start justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    <flux:icon.cube class="size-5" />
                </span>
                <flux:badge :color="$lowStockCount > 0 ? 'amber' : 'emerald'" :icon="$lowStockCount > 0 ? 'exclamation-triangle' : 'check-circle'">
                    {{ $lowStockCount > 0 ? 'À contrôler' : 'Stock sain' }}
                </flux:badge>
            </div>
            <flux:text size="sm" class="mt-5">Alertes de stock</flux:text>
            <flux:heading class="mt-1 text-2xl! font-semibold!">{{ $lowStockCount }}</flux:heading>
            <flux:text size="sm" class="mt-1">sur {{ $totalProducts }} références</flux:text>
        </flux:card>

        <flux:card class="p-5!">
            <div class="flex items-start justify-between gap-4">
                <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <flux:icon.wallet class="size-5" />
                </span>
                <flux:badge :color="$activeSession ? 'emerald' : 'zinc'">{{ $activeSession ? 'Ouverte' : 'Fermée' }}</flux:badge>
            </div>
            <flux:text size="sm" class="mt-5">Session de caisse</flux:text>
            <flux:heading class="mt-1 text-2xl! font-semibold!">@if($activeSession)<x-money :amount="$activeSession->opening_balance" />@else—@endif</flux:heading>
            <flux:text size="sm" class="mt-1">{{ $activeSession ? 'Ouverte par '.$activeSession->user?->name : 'Aucune session active' }}</flux:text>
        </flux:card>
    </section>

    @if($isManager)
        <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
            <flux:card class="min-w-0 p-0!">
                <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                    <div>
                        <flux:heading size="lg">Évolution des ventes</flux:heading>
                        <flux:text size="sm">Chiffre d’affaires sur {{ $period }} jours</flux:text>
                    </div>
                    <div class="sm:text-right">
                        <flux:heading class="text-xl!"><x-money :amount="$monthSales" /></flux:heading>
                        <flux:text size="sm">Total du mois en {{ \App\Support\DisplayCurrency::code() }}</flux:text>
                    </div>
                </div>

                <div class="p-5">
                    <div class="grid h-64 items-end gap-1.5 sm:gap-2" style="grid-template-columns: repeat({{ $chart->count() }}, minmax(0, 1fr));" role="img" aria-label="Graphique des ventes sur {{ $period }} jours">
                        @foreach($chart as $point)
                            <div class="group flex h-full min-w-0 flex-col items-center justify-end gap-2" title="{{ $point['date'] }} : {{ \App\Support\DisplayCurrency::format($point['value']) }}">
                                <span class="hidden text-[10px] font-medium text-zinc-500 group-hover:block xl:block">{{ $point['value'] > 0 ? \App\Support\DisplayCurrency::format($point['value'], null, false) : '0' }}</span>
                                <div class="relative flex h-44 w-full items-end overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800/80">
                                    <div
                                        class="w-full rounded-lg bg-violet-500 transition-[height,background-color] duration-500 group-hover:bg-violet-600 dark:bg-violet-400 dark:group-hover:bg-violet-300"
                                        style="height: {{ max(($point['value'] / $maxChartValue) * 100, 3) }}%;"
                                        aria-label="{{ $point['date'] }} : {{ \App\Support\DisplayCurrency::format($point['value']) }}"
                                    ></div>
                                </div>
                                <span class="w-full truncate text-center text-[10px] text-zinc-500">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-5!">
                <flux:heading size="lg">Paiements du jour</flux:heading>
                <flux:text size="sm" class="mt-1">Répartition par moyen de paiement</flux:text>
                @php($paymentTotal = max((float) $payments->sum('value'), 1))
                <div class="mt-6 space-y-6">
                    @foreach($payments as $payment)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium">{{ $payment['label'] }}</span>
                                <span class="font-semibold tabular-nums"><x-money :amount="$payment['value']" /></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                @switch($payment['color'])
                                    @case('emerald')<div class="h-full rounded-full bg-emerald-500" style="width: {{ ($payment['value'] / $paymentTotal) * 100 }}%"></div>@break
                                    @case('amber')<div class="h-full rounded-full bg-amber-500" style="width: {{ ($payment['value'] / $paymentTotal) * 100 }}%"></div>@break
                                    @default<div class="h-full rounded-full bg-violet-500" style="width: {{ ($payment['value'] / $paymentTotal) * 100 }}%"></div>
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex items-end justify-between border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <flux:text>Total encaissé</flux:text>
                    <flux:heading class="text-xl!"><x-money :amount="$payments->sum('value')" /></flux:heading>
                </div>
            </flux:card>
        </section>
    @endif

    <flux:card class="overflow-hidden p-0!" aria-label="Accès rapide">
        <div class="flex flex-col gap-3 border-b border-zinc-200 bg-zinc-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-950/35 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"><flux:icon.bolt class="size-5" /></span>
                <div><flux:heading size="lg">Accès rapide</flux:heading><flux:text size="sm">Ouvrez immédiatement vos espaces de travail essentiels.</flux:text></div>
            </div>
            <flux:badge color="violet" icon="cursor-arrow-rays">Navigation directe</flux:badge>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            <flux:button href="{{ route('pos') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-violet-50! p-4! text-left! ring-1! ring-violet-200! hover:bg-violet-100! dark:bg-violet-500/10! dark:ring-violet-500/20! dark:hover:bg-violet-500/15!">
                <span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-600 text-white shadow-sm"><flux:icon.shopping-cart class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Point de vente</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Encaisser une commande</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-violet-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
            </flux:button>
            <flux:button href="{{ route('appointments') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-sky-50! p-4! text-left! ring-1! ring-sky-200! hover:bg-sky-100! dark:bg-sky-500/10! dark:ring-sky-500/20! dark:hover:bg-sky-500/15!">
                <span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-600 text-white shadow-sm"><flux:icon.calendar-days class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Rendez-vous</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Organiser le planning</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-sky-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
            </flux:button>
            <flux:button href="{{ route('customers') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-blue-50! p-4! text-left! ring-1! ring-blue-200! hover:bg-blue-100! dark:bg-blue-500/10! dark:ring-blue-500/20! dark:hover:bg-blue-500/15!">
                <span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white shadow-sm"><flux:icon.users class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Clients</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Profils et fidélité</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-blue-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
            </flux:button>
            @if($isManager)
                <flux:button href="{{ route('inventory') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-amber-50! p-4! text-left! ring-1! ring-amber-200! hover:bg-amber-100! dark:bg-amber-500/10! dark:ring-amber-500/20! dark:hover:bg-amber-500/15!"><span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-500 text-white shadow-sm"><flux:icon.cube class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Inventaire</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Stock et mouvements</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-amber-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" /></flux:button>
                <flux:button href="{{ route('accounting') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-emerald-50! p-4! text-left! ring-1! ring-emerald-200! hover:bg-emerald-100! dark:bg-emerald-500/10! dark:ring-emerald-500/20! dark:hover:bg-emerald-500/15!"><span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white shadow-sm"><flux:icon.building-library class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Comptabilité</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Journal OHADA</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-emerald-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" /></flux:button>
                <flux:button href="{{ route('reports') }}" wire:navigate variant="ghost" class="group h-auto! min-h-24! justify-between! rounded-2xl! bg-rose-50! p-4! text-left! ring-1! ring-rose-200! hover:bg-rose-100! dark:bg-rose-500/10! dark:ring-rose-500/20! dark:hover:bg-rose-500/15!"><span class="flex min-w-0 items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-600 text-white shadow-sm"><flux:icon.chart-bar-square class="size-5" /></span><span><span class="block font-semibold text-zinc-950 dark:text-white">Rapports</span><span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Analyses et exports</span></span></span><flux:icon.arrow-up-right class="size-4 shrink-0 text-rose-500 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" /></flux:button>
            @endif
        </div>
    </flux:card>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
        <flux:card class="overflow-hidden p-0!">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-5 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg">Dernières ventes</flux:heading>
                    <flux:text size="sm">Les six encaissements les plus récents</flux:text>
                </div>
                @if($isManager)<flux:button href="{{ route('reports') }}" wire:navigate variant="ghost" size="sm" icon:trailing="arrow-right">Rapports</flux:button>@endif
            </div>

            @if($recentSales->isEmpty())
                <div class="p-5"><flux:callout icon="receipt-refund" heading="Aucune vente" text="Les prochains encaissements apparaîtront ici." /></div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column><x-table-heading icon="receipt-percent">Ticket</x-table-heading></flux:table.column>
                        <flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column>
                        <flux:table.column class="max-md:hidden"><x-table-heading icon="identification">Caissier</x-table-heading></flux:table.column>
                        <flux:table.column class="max-sm:hidden"><x-table-heading icon="credit-card">Paiement</x-table-heading></flux:table.column>
                        <flux:table.column align="end"><x-table-heading icon="banknotes">Montant</x-table-heading></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($recentSales as $sale)
                            <flux:table.row :key="$sale->id">
                                <flux:table.cell>
                                    <div class="font-medium">#{{ str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    <flux:text size="sm">{{ $sale->created_at->diffForHumans() }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell variant="strong">{{ $sale->customer?->name ?? 'Client de passage' }}</flux:table.cell>
                                <flux:table.cell class="max-md:hidden">{{ $sale->user?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="max-sm:hidden"><flux:badge color="zinc">{{ strtoupper($sale->payment_method) }}</flux:badge></flux:table.cell>
                                <flux:table.cell align="end" variant="strong" class="tabular-nums"><x-money :amount="$sale->total_amount" /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        <div class="space-y-4">
            <flux:card class="p-5!">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Planning du jour</flux:heading>
                        <flux:text size="sm">{{ $appointments->count() }} rendez-vous</flux:text>
                    </div>
                    <flux:button href="{{ route('appointments') }}" wire:navigate icon="arrow-right" variant="ghost" square tooltip="Ouvrir le planning" />
                </div>
                <div class="mt-5 space-y-3">
                    @forelse($appointments->take(4) as $appointment)
                        <div class="flex items-start gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                            <flux:badge :color="$appointment->status === 'confirmed' ? 'emerald' : 'amber'">{{ $appointment->date_time->format('H:i') }}</flux:badge>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $appointment->customer?->name ?? 'Client' }}</p>
                                <flux:text size="sm" class="truncate">{{ $appointment->service?->name ?? 'Service' }}</flux:text>
                            </div>
                        </div>
                    @empty
                        <flux:callout icon="calendar-days" heading="Agenda libre" text="Aucun rendez-vous aujourd’hui." />
                    @endforelse
                </div>
            </flux:card>

            @if($isManager)
                <flux:card class="p-5!">
                    <div class="flex items-center justify-between gap-3">
                        <div><flux:heading size="lg">Fidélité</flux:heading><flux:text size="sm">{{ $totalCustomers }} clients · {{ $activeSubscriptions }} abonnés</flux:text></div>
                        <flux:button href="{{ route('customers') }}" wire:navigate icon="users" variant="ghost" square tooltip="Gérer les clients" />
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($topCustomers as $index => $customer)
                            <div class="flex items-center gap-3">
                                <flux:badge :color="$index === 0 ? 'amber' : 'zinc'">{{ $index + 1 }}</flux:badge>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $customer->name }}</span>
                                <span class="text-sm font-semibold tabular-nums text-violet-600 dark:text-violet-400">{{ number_format($customer->points) }} pts</span>
                            </div>
                        @empty
                            <flux:text size="sm">Aucun client enregistré.</flux:text>
                        @endforelse
                    </div>
                </flux:card>
            @endif
        </div>
    </section>
</div>
