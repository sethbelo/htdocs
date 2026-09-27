<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header title="Suivi des commandes" eyebrow="Pilotage"
                      subtitle="Chaque dossier suit le parcours devis → validation → facture → encaissement → atelier / chantier → livraison.">
        <x-slot:actions>
            <div class="segmented">
                <button type="button" wire:click="$set('view', 'kanban')" aria-pressed="{{ $view === 'kanban' ? 'true' : 'false' }}"><x-ui.icon name="view-columns" class="inline size-4" /> Tableau</button>
                <button type="button" wire:click="$set('view', 'liste')" aria-pressed="{{ $view === 'liste' ? 'true' : 'false' }}"><x-ui.icon name="list-bullet" class="inline size-4" /> Liste</button>
            </div>
            @if (auth()->user()->canManage('quotes'))
                <x-ui.button variant="accent" icon="plus" :href="route('quotes.create')">Nouveau devis</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. BANDEAU DES ÉTAPES ════════════════════════════════════════════ --}}
    <div class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8">
        @foreach ($stages as $key => [$label, $tone])
            @php $row = $counts->get($key); @endphp
            <button type="button" wire:click="$set('stage', '{{ $stage === $key ? '' : $key }}'); $set('view', 'liste')"
                    @class(['card flex flex-col items-start px-3 py-2.5 text-left transition hover:border-brand-200', 'ring-2 ring-sun-400' => $stage === $key])>
                <x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge>
                <span class="mt-1.5 font-display text-[20px] leading-none font-semibold">{{ $row->c ?? 0 }}</span>
                <span class="mt-1 text-[11.5px] text-fg-subtle tabular-nums">{{ money($row->t ?? 0, 'USD', 0) }}</span>
            </button>
        @endforeach
    </div>

    {{-- ═══ 3. FILTRES ═══════════════════════════════════════════════════════ --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
        <x-ui.search wire:model.live.debounce.300ms="search" placeholder="N°, client, objet…" />
        <select wire:model.live="kind" class="input sm:w-56">
            <option value="">Toutes les natures</option>
            @foreach (config('erp.order_kinds') as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
        </select>
        @if ($stage || $search || $kind)
            <x-ui.button variant="ghost" size="sm" icon="x-mark" wire:click="$set('stage', ''); $set('search', ''); $set('kind', '')">Réinitialiser</x-ui.button>
        @endif
    </div>

    {{-- ═══ 4A. VUE TABLEAU (KANBAN) ═════════════════════════════════════════ --}}
    @if ($view === 'kanban')
        <div class="kanban">
            @foreach ($stages->except(['cloture']) as $key => [$label, $tone])
                @php $orders = $columns->get($key, collect()); @endphp
                <section class="kanban-col">
                    <header class="flex items-center justify-between px-2 pt-1 pb-2.5">
                        <span class="flex items-center gap-2"><x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge><span class="text-[12px] font-semibold text-fg-subtle">{{ $orders->count() }}</span></span>
                        <span class="text-[11.5px] text-fg-subtle tabular-nums">{{ money($orders->sum('total'), 'USD', 0) }}</span>
                    </header>
                    <div class="flex min-h-24 flex-col gap-2">
                        @forelse ($orders as $order)
                            <a href="{{ route('orders.show', $order) }}" wire:navigate class="kanban-card block cursor-pointer">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-[11.5px] font-semibold text-fg-subtle">{{ $order->number }}</span>
                                    @if (in_array($order->priority, ['haute', 'urgente']))<x-ui.status map="priority" :value="$order->priority" />@endif
                                </div>
                                <p class="mt-1 line-clamp-2 text-[13.5px] leading-snug font-semibold">{{ $order->title }}</p>
                                <p class="mt-1 truncate text-[12px] text-fg-muted">{{ $order->contact?->display_name }}</p>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="font-display text-[15px] font-semibold tabular-nums">{{ money($order->total, $order->currency_code, 0) }}</span>
                                    <span class="flex items-center gap-1 text-[11px] text-fg-subtle">
                                        <x-ui.icon :name="['fabrication' => 'wrench-screwdriver', 'construction' => 'home-modern', 'service' => 'briefcase', 'vente' => 'shopping-bag'][$order->kind] ?? 'cube'" class="size-3.5" />
                                        {{ config('erp.order_kinds.'.$order->kind) }}
                                    </span>
                                </div>
                                @if ((float) $order->total > 0 && $order->invoice_id)
                                    <x-ui.progress :value="round($order->paidRatio() * 100)" class="mt-2.5" :tone="$order->balanceDue() > 0 ? 'lagoon' : 'emerald'" />
                                @endif
                                @if ($order->manufacturingOrder && $key === 'fabrication')
                                    <p class="mt-2 flex items-center gap-1.5 text-[11.5px] text-fg-muted"><x-ui.icon name="cog-6-tooth" class="size-3.5" /> {{ $order->manufacturingOrder->number }} · {{ $order->manufacturingOrder->progress }} %</p>
                                @endif
                                @if ($order->delivery_date)
                                    <p @class(['mt-1 flex items-center gap-1.5 text-[11.5px]', 'text-flame-600 font-semibold' => $order->delivery_date->lt(today()), 'text-fg-subtle' => ! $order->delivery_date->lt(today())])><x-ui.icon name="calendar" class="size-3.5" /> Livraison {{ $order->delivery_date->format('d/m/Y') }}</p>
                                @endif
                            </a>
                        @empty
                            <p class="rounded-lg border border-dashed border-line-strong px-3 py-6 text-center text-[12px] text-fg-subtle">Aucune commande</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

    {{-- ═══ 4B. VUE LISTE ═════════════════════════════════════════════════════ --}}
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Commande</th><th>Client</th><th>Nature</th><th>Étape</th><th>Livraison</th><th class="num">Montant</th><th class="num">Encaissé</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr wire:key="o-{{ $order->id }}">
                                <td><a href="{{ route('orders.show', $order) }}" wire:navigate class="link">{{ $order->number }}</a><p class="max-w-[260px] truncate text-[12px] text-fg-subtle">{{ $order->title }}</p></td>
                                <td class="max-w-[200px] truncate">{{ $order->contact?->display_name }}</td>
                                <td class="text-fg-muted">{{ config('erp.order_kinds.'.$order->kind) }}</td>
                                <td><x-ui.status map="order_stage" :value="$order->stage" /></td>
                                <td class="whitespace-nowrap text-fg-muted">{{ $order->delivery_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="num font-semibold">{{ money($order->total, $order->currency_code) }}</td>
                                <td class="num w-36"><x-ui.progress :value="round($order->paidRatio() * 100)" :tone="$order->balanceDue() > 0 ? 'lagoon' : 'emerald'" /></td>
                                <td class="text-right"><x-ui.button variant="ghost" size="sm" square icon="chevron-right" :href="route('orders.show', $order)" aria-label="Ouvrir" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="arrow-path-rounded-square" title="Aucune commande trouvée" text="Modifiez les filtres ou créez un devis pour ouvrir un dossier." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="border-t border-line px-4 py-3">{{ $orders->links('components.ui.pagination') }}</div>@endif
        </div>
    @endif
</div>
