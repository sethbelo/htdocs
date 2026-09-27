<div class="space-y-6">
    {{-- En-tête partagé : analyse des ventes, rentabilité et export PDF. --}}
    <x-page-hero
        title="Ventes & rentabilité"
        eyebrow="Pilotage commercial"
        description="Analysez les transactions, le chiffre d’affaires, la TVA et la marge par catégorie."
        icon="shopping-bag"
        badge-icon="arrow-trending-up"
        illustration="sales"
    >
        <x-slot:actions>
            <flux:badge color="emerald" icon="signal" class="bg-emerald-400/15! text-emerald-100! ring-emerald-300/20!">Synchronisé</flux:badge>
            @can('export sales')
                <flux:button href="{{ route('sales.pdf', array_filter(['start' => $startDate, 'end' => $endDate, 'category' => $category ?: null])) }}" variant="primary" color="zinc" icon="arrow-down-tray" class="bg-white! text-indigo-900! hover:bg-violet-50!">Exporter le PDF</flux:button>
            @endcan
        </x-slot:actions>
    </x-page-hero>

    <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1.2fr_1.4fr_auto] xl:items-end">
            <flux:input type="date" wire:model.live="startDate" label="Date de début" icon="calendar-days" />
            <flux:input type="date" wire:model.live="endDate" label="Date de fin" icon="calendar-days" />
            <flux:select wire:model.live="category" label="Catégorie"><flux:select.option value="">Toutes les catégories</flux:select.option>@foreach($availableCategories as $availableCategory)<flux:select.option value="{{ $availableCategory }}">{{ $availableCategory }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model.live.debounce.300ms="search" label="Recherche" icon="magnifying-glass" placeholder="N° vente, client ou produit" />
            <flux:button wire:click="$refresh" icon="arrow-path" class="w-full xl:w-auto">Actualiser</flux:button>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['banknotes','Chiffre d’affaires TTC',$totals['revenue'],'violet','bg-violet-500/10 text-violet-600 dark:text-violet-300'],
            ['receipt-percent','TVA collectée',$totals['tax'],'amber','bg-amber-500/10 text-amber-600 dark:text-amber-300'],
            ['calculator','Coût des ventes',$totals['cost'],'sky','bg-sky-500/10 text-sky-600 dark:text-sky-300'],
            ['arrow-trending-up','Bénéfice brut',$totals['profit'],'emerald','bg-emerald-500/10 text-emerald-600 dark:text-emerald-300'],
        ] as [$icon,$label,$value,$color,$iconClasses])
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><div class="flex items-start justify-between gap-3"><span class="grid size-10 place-items-center rounded-xl {{ $iconClasses }}"><flux:icon :name="$icon" class="size-5" /></span><flux:badge color="{{ $color }}">{{ $currency }}</flux:badge></div><flux:text size="sm" class="mt-4">{{ $label }}</flux:text><div class="mt-1 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white"><x-money :amount="$value" /></div></article>
        @endforeach
        <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><div class="flex items-start justify-between gap-3"><span class="grid size-10 place-items-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300"><flux:icon.chart-pie class="size-5" /></span><flux:badge color="rose">Marge</flux:badge></div><flux:text size="sm" class="mt-4">Taux de marge brute</flux:text><div class="mt-1 text-2xl font-bold tabular-nums text-zinc-950 dark:text-white">{{ number_format($totals['margin'], 1, ',', ' ') }} %</div></article>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(22rem,.9fr)]">
        <article class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-5 dark:border-zinc-800"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-300"><flux:icon.squares-2x2 class="size-5" /></span><div><flux:heading size="lg">Performance par catégorie</flux:heading><flux:text size="sm">CA, bénéfice et poids dans les ventes</flux:text></div></div><flux:badge color="violet">{{ $categories->count() }} catégorie(s)</flux:badge></div>
            <div class="overflow-x-auto"><flux:table><flux:table.columns><flux:table.column><x-table-heading icon="tag">Catégorie</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">CA TTC</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="receipt-percent">TVA</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="arrow-trending-up">Bénéfice</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="chart-bar">Part</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
                @forelse($categories as $categoryRow)<flux:table.row><flux:table.cell><div class="font-semibold">{{ $categoryRow['name'] }}</div><div class="text-xs text-zinc-500">{{ $categoryRow['orders'] }} vente(s) · {{ number_format($categoryRow['quantity'], 3, ',', ' ') }} unité(s)</div></flux:table.cell><flux:table.cell align="end" class="font-semibold tabular-nums"><x-money :amount="$categoryRow['revenue']" /></flux:table.cell><flux:table.cell align="end" class="tabular-nums"><x-money :amount="$categoryRow['tax']" /></flux:table.cell><flux:table.cell align="end"><span class="font-semibold tabular-nums {{ $categoryRow['profit'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}"><x-money :amount="$categoryRow['profit']" /></span></flux:table.cell><flux:table.cell><div class="min-w-28"><div class="mb-1 flex justify-between text-xs"><span>{{ number_format($categoryRow['share'], 1, ',', ' ') }} %</span><span>{{ number_format($categoryRow['margin'], 1, ',', ' ') }} % marge</span></div><div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-violet-500" style="width:{{ min(100, $categoryRow['share']) }}%"></div></div></div></flux:table.cell></flux:table.row>
                @empty<flux:table.row><flux:table.cell colspan="5" class="py-12! text-center"><x-table-empty icon="chart-bar" title="Aucune vente sur cette période" description="Modifiez les dates ou la catégorie sélectionnée." /></flux:table.cell></flux:table.row>@endforelse
            </flux:table.rows></flux:table></div>
        </article>

        <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3"><div><flux:heading size="lg">Indicateurs généraux</flux:heading><flux:text size="sm">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</flux:text></div><span class="grid size-10 place-items-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-300"><flux:icon.presentation-chart-line class="size-5" /></span></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">@foreach([['Transactions',$totals['orders'],'shopping-bag',false],['Articles vendus',number_format($totals['quantity'],3,',',' '),'cube',false],['Panier moyen',$totals['average_order'],'calculator',true],['CA hors TVA',$totals['net_before_tax'],'document-chart-bar',true]] as [$label,$value,$icon,$isMoney])<div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60"><div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-zinc-500"><flux:icon :name="$icon" class="size-4" />{{ $label }}</div><div class="mt-2 text-xl font-bold tabular-nums">@if($isMoney)<x-money :amount="$value" />@else{{ $value }}@endif</div></div>@endforeach</div>
        </article>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-5 dark:border-zinc-800"><div><flux:heading size="lg">Détail des ventes</flux:heading><flux:text size="sm">Transactions, clients, paiements, TVA et bénéfice estimé</flux:text></div><flux:select wire:model.live="perPage" class="w-36" aria-label="Ventes par page"><flux:select.option value="15">15 / page</flux:select.option><flux:select.option value="30">30 / page</flux:select.option><flux:select.option value="50">50 / page</flux:select.option></flux:select></div>
        <div class="overflow-x-auto"><flux:table><flux:table.columns><flux:table.column><x-table-heading icon="hashtag">Vente</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="calendar-days">Date / caissier</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="user">Client</x-table-heading></flux:table.column><flux:table.column><x-table-heading icon="credit-card">Paiement</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="receipt-percent">TVA</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="banknotes">Total TTC</x-table-heading></flux:table.column><flux:table.column align="end"><x-table-heading icon="arrow-trending-up">Bénéfice</x-table-heading></flux:table.column></flux:table.columns><flux:table.rows>
            @forelse($sales as $sale)@php($saleRows = $rows->where('sale_id', $sale->id))<flux:table.row wire:key="sale-{{ $sale->id }}"><flux:table.cell><div class="font-semibold">VTE-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div><div class="text-xs text-zinc-500">{{ $sale->items->count() }} ligne(s)</div></flux:table.cell><flux:table.cell><div>{{ $sale->created_at->format('d/m/Y H:i') }}</div><div class="text-xs text-zinc-500">{{ $sale->user?->name ?? '—' }}</div></flux:table.cell><flux:table.cell>{{ $sale->customer?->name ?? 'Client de passage' }}</flux:table.cell><flux:table.cell><flux:badge color="zinc">{{ str_replace('_', ' ', ucfirst($sale->payment_method)) }}</flux:badge></flux:table.cell><flux:table.cell align="end" class="tabular-nums"><x-money :amount="$sale->tax_amount" /></flux:table.cell><flux:table.cell align="end" class="font-semibold tabular-nums"><x-money :amount="$sale->total_amount" /></flux:table.cell><flux:table.cell align="end"><span class="font-semibold tabular-nums {{ $saleRows->sum('profit') >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}"><x-money :amount="$saleRows->sum('profit')" /></span></flux:table.cell></flux:table.row>
            @empty<flux:table.row><flux:table.cell colspan="7" class="py-14! text-center"><x-table-empty icon="shopping-bag" title="Aucune transaction trouvée" description="Aucune vente ne correspond aux filtres actifs." /></flux:table.cell></flux:table.row>@endforelse
        </flux:table.rows></flux:table></div>
        @if($sales->hasPages())<div class="border-t border-zinc-200 p-4 dark:border-zinc-800"><flux:pagination :paginator="$sales" scroll-to="body" /></div>@endif
    </section>
</div>
