@php
    $selectedPriceGrid = $priceGridTemplates[$priceGridTemplate] ?? reset($priceGridTemplates);
    $priceGridArticleCount = $priceGridGroups->sum(fn ($items) => $items->count());
    $priceGridExportUrl = route('inventory.price-grid.pdf', [
        'template' => $priceGridTemplate,
        'category' => $priceGridCategory,
        'currency' => $priceGridCurrency,
        'show_sku' => $priceGridShowSku ? 1 : 0,
        'show_tax' => $priceGridShowTax ? 1 : 0,
    ]);
@endphp

<div class="space-y-5">
    <flux:card class="overflow-hidden p-0!">
        <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 dark:border-zinc-800 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                    <flux:icon.rectangle-stack class="size-5" />
                </span>
                <div>
                    <flux:heading size="lg">Atelier de grilles tarifaires</flux:heading>
                    <flux:text size="sm" class="mt-1">Choisissez une identité visuelle, contrôlez l’aperçu puis exportez un PDF A4 prêt à diffuser.</flux:text>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:badge color="violet" icon="squares-2x2">12 modèles</flux:badge>
                <flux:badge color="emerald" icon="tag">{{ $priceGridArticleCount }} article{{ $priceGridArticleCount > 1 ? 's' : '' }}</flux:badge>
                <flux:button href="{{ $priceGridExportUrl }}" variant="primary" color="violet" icon="arrow-down-tray">Exporter en PDF</flux:button>
            </div>
        </div>

        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4" role="list" aria-label="Modèles de grilles tarifaires">
            @foreach($priceGridTemplates as $templateKey => $template)
                <button
                    type="button"
                    wire:click="selectPriceGridTemplate('{{ $templateKey }}')"
                    wire:key="price-grid-template-{{ $templateKey }}"
                    @class([
                        'group relative overflow-hidden rounded-2xl border p-4 text-left transition duration-200 hover:-translate-y-0.5 hover:shadow-md',
                        'ring-2 ring-violet-500 ring-offset-2 dark:ring-offset-zinc-900' => $priceGridTemplate === $templateKey,
                        'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $priceGridTemplate !== $templateKey,
                    ])
                    style="--model-accent: {{ $template['accent'] }}; --model-soft: {{ $template['soft'] }}; --model-border: {{ $template['border'] }}"
                    aria-pressed="{{ $priceGridTemplate === $templateKey ? 'true' : 'false' }}"
                >
                    <svg class="pointer-events-none absolute -right-5 -top-6 size-24 opacity-20 transition group-hover:scale-110" viewBox="0 0 96 96" fill="none" aria-hidden="true">
                        <circle cx="48" cy="48" r="35" fill="var(--model-soft)" stroke="var(--model-accent)" stroke-width="2" />
                        <path d="M18 58 40 36l13 13 25-27M61 22h17v17" stroke="var(--model-accent)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="relative flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl" style="background:var(--model-soft);color:var(--model-accent)">
                            <flux:icon :name="$template['icon']" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-950 dark:text-white">{{ $template['label'] }}</span>
                                @if($priceGridTemplate === $templateKey)<flux:icon.check-circle class="size-4 text-violet-600" />@endif
                            </div>
                            <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $template['description'] }}</p>
                        </div>
                    </div>
                    <div class="relative mt-4 grid grid-cols-[1fr_auto] gap-2 border-t pt-3" style="border-color:var(--model-border)">
                        <span class="h-2 rounded-full" style="background:var(--model-soft)"></span>
                        <span class="h-2 w-10 rounded-full" style="background:var(--model-accent)"></span>
                        <span class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800"></span>
                        <span class="h-2 w-7 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
                    </div>
                </button>
            @endforeach
        </div>
    </flux:card>

    <flux:card class="p-4! sm:p-5!">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_12rem_12rem_auto_auto] xl:items-end">
            <flux:select wire:model.live="priceGridCategory" label="Catégorie à publier" icon="tag">
                <flux:select.option value="">Toutes les catégories</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->name }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <div><flux:label>Devise</flux:label><div class="mt-2 flex h-10 items-center gap-2 rounded-lg bg-zinc-100 px-3 text-sm font-semibold dark:bg-zinc-800"><flux:icon.banknotes class="size-4 text-violet-600" />{{ \App\Support\DisplayCurrency::baseCode() }}</div></div>
            <div class="flex min-h-10 items-center"><flux:switch wire:model.live="priceGridShowSku" label="Afficher les SKU" /></div>
            <div class="flex min-h-10 items-center"><flux:switch wire:model.live="priceGridShowTax" label="Mention TVA" /></div>
            <flux:button href="{{ $priceGridExportUrl }}" variant="primary" icon="document-arrow-down" class="w-full xl:w-auto">PDF A4</flux:button>
        </div>
    </flux:card>

    <flux:card class="overflow-hidden p-0!">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.eye class="size-4" /></span>
                <div><flux:heading>Aperçu en temps réel</flux:heading><flux:text size="sm">Modèle {{ $selectedPriceGrid['label'] }} · format A4 portrait</flux:text></div>
            </div>
            <flux:badge color="zinc" icon="printer">Prêt à imprimer</flux:badge>
        </div>

        <div class="bg-zinc-100 p-3 dark:bg-zinc-950 sm:p-6 lg:p-8">
            <article
                class="yb-price-sheet mx-auto min-h-[72rem] w-full max-w-[52rem] overflow-hidden bg-white text-zinc-900 shadow-xl"
                data-price-grid-template="{{ $priceGridTemplate }}"
                data-price-grid-layout="{{ $selectedPriceGrid['layout'] }}"
                data-price-grid-shape="{{ $selectedPriceGrid['shape'] }}"
                style="--pg-accent:{{ $selectedPriceGrid['accent'] }};--pg-dark:{{ $selectedPriceGrid['dark'] }};--pg-soft:{{ $selectedPriceGrid['soft'] }};--pg-border:{{ $selectedPriceGrid['border'] }}"
            >
                <header class="yb-price-header relative overflow-hidden px-6 py-7 sm:px-9 sm:py-9">
                    <svg class="absolute -right-10 -top-14 size-52 opacity-20" viewBox="0 0 220 220" fill="none" aria-hidden="true">
                        <circle cx="110" cy="110" r="82" stroke="currentColor" stroke-width="2" />
                        <circle cx="110" cy="110" r="55" stroke="currentColor" stroke-width="8" stroke-dasharray="14 12" />
                        <path d="M36 142 85 93l31 30 67-70M148 53h35v35" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="relative flex items-start justify-between gap-5">
                        <div class="min-w-0">
                            <img src="{{ $priceGridLogoUrl }}" alt="Logo {{ $priceGridCompany['name'] ?? 'Société' }}" class="yb-price-logo mb-5 h-12 max-w-48 object-contain object-left sm:h-14">
                            <p class="yb-price-kicker text-xs font-bold uppercase tracking-[.24em]">Catalogue officiel</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Grille tarifaire</h2>
                            <p class="mt-2 max-w-md text-sm opacity-75">Des prix clairs, organisés par catégorie et présentés dans la devise de votre choix.</p>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="text-sm font-black">{{ $priceGridCompany['name'] ?? 'Yeboox' }}</p>
                            <p class="mt-1 text-xs opacity-70">{{ $priceGridCompany['address'] ?? '' }}</p>
                            <p class="text-xs opacity-70">{{ collect([$priceGridCompany['phone'] ?? '', $priceGridCompany['email'] ?? ''])->filter()->join(' · ') }}</p>
                        </div>
                    </div>
                </header>

                <div class="yb-price-content space-y-8 px-6 py-8 sm:px-9">
                    @forelse($priceGridGroups as $categoryName => $categoryProducts)
                        <section class="yb-price-category break-inside-avoid">
                            <div class="yb-price-category-title mb-4 flex items-center gap-3">
                                <span class="yb-price-category-icon grid size-9 shrink-0 place-items-center rounded-xl">
                                    <flux:icon.tag class="size-4" />
                                </span>
                                <div class="min-w-0 flex-1"><h3 class="text-lg font-black">{{ $categoryName }}</h3><p class="text-xs opacity-60">{{ $categoryProducts->count() }} référence{{ $categoryProducts->count() > 1 ? 's' : '' }}</p></div>
                                <svg class="h-5 w-24 opacity-35" viewBox="0 0 96 20" fill="none" aria-hidden="true"><path d="M1 10h74M68 3l8 7-8 7M82 3h13v14H82z" stroke="currentColor" stroke-width="2" /></svg>
                            </div>
                            <div class="yb-price-items">
                                @foreach($categoryProducts as $product)
                                    <div class="yb-price-item">
                                        <div class="min-w-0 flex-1">
                                            <div class="yb-price-name truncate font-bold">{{ $product->name }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] opacity-60">
                                                @if($priceGridShowSku)<span>{{ $product->sku ?: 'Sans SKU' }}</span>@endif
                                                <span>{{ ['product' => 'Bien', 'service' => 'Service', 'combo' => 'Combo'][$product->type] ?? ucfirst($product->type) }}</span>
                                                @if($product->type === 'product' && $product->unitOfMeasure)<span>par {{ $product->unitOfMeasure->symbol }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="yb-price-value shrink-0 text-right">
                                            <strong>{{ \App\Support\DisplayCurrency::format($product->grossUnitPrice(), $priceGridCurrency) }}</strong>
                                            @if($priceGridShowTax)<span class="block text-[9px] font-semibold uppercase tracking-wide opacity-55">TTC · TVA {{ number_format((float) $product->sales_tax_rate, 0) }}%</span>@endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="py-24 text-center">
                            <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-zinc-100 text-zinc-400"><flux:icon.tag class="size-6" /></span>
                            <h3 class="mt-4 font-bold">Aucun article à publier</h3>
                            <p class="mt-1 text-sm text-zinc-500">Activez des articles ou choisissez une autre catégorie.</p>
                        </div>
                    @endforelse
                </div>

                <footer class="yb-price-footer mx-6 flex items-center justify-between gap-4 border-t py-5 text-[10px] sm:mx-9">
                    <span>Tarifs édités le {{ now()->format('d/m/Y') }} · Sous réserve de modification</span>
                    <strong>{{ $priceGridCurrency }} · {{ \App\Support\DisplayCurrency::rateLabel() }}</strong>
                </footer>
            </article>
        </div>
    </flux:card>
</div>

@once
    <style>
        .yb-price-sheet { --pg-radius: 1rem; border: 1px solid var(--pg-border); }
        .yb-price-header { background: var(--pg-soft); color: var(--pg-dark); border-bottom: 4px solid var(--pg-accent); }
        .yb-price-header svg { color: var(--pg-accent); }
        .yb-price-kicker, .yb-price-category-title h3, .yb-price-value strong { color: var(--pg-accent); }
        .yb-price-category-icon { background: var(--pg-soft); color: var(--pg-accent); }
        .yb-price-items { display: grid; gap: .65rem; }
        .yb-price-item { display: flex; align-items: center; gap: 1rem; min-width: 0; padding: .85rem 1rem; border: 1px solid var(--pg-border); border-radius: var(--pg-radius); background: #fff; }
        .yb-price-value strong { display: block; font-size: .95rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .yb-price-footer { border-color: var(--pg-border); color: #71717a; }
        .yb-price-sheet[data-price-grid-layout="cards"] .yb-price-items,
        .yb-price-sheet[data-price-grid-layout="columns"] .yb-price-items { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .yb-price-sheet[data-price-grid-layout="mosaic"] .yb-price-items { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .yb-price-sheet[data-price-grid-layout="mosaic"] .yb-price-item { display: block; min-height: 6.5rem; }
        .yb-price-sheet[data-price-grid-layout="mosaic"] .yb-price-value { margin-top: 1rem; text-align: left; }
        .yb-price-sheet[data-price-grid-layout="table"] .yb-price-items { gap: 0; border-top: 2px solid var(--pg-dark); }
        .yb-price-sheet[data-price-grid-layout="table"] .yb-price-item { border-width: 0 0 1px; border-radius: 0; padding-inline: .25rem; }
        .yb-price-sheet[data-price-grid-layout="menu"] .yb-price-item { border-width: 0 0 1px; border-style: dashed; border-radius: 0; padding-inline: .25rem; }
        .yb-price-sheet[data-price-grid-shape="square"] { --pg-radius: 0; }
        .yb-price-sheet[data-price-grid-shape="soft"] { --pg-radius: .45rem; }
        .yb-price-sheet[data-price-grid-shape="pill"] { --pg-radius: 999px; }
        .yb-price-sheet[data-price-grid-shape="ticket"] .yb-price-item { border-left: 5px solid var(--pg-accent); border-radius: .25rem; }
        .yb-price-sheet[data-price-grid-shape="cut"] .yb-price-item { border-radius: 0 1rem 0 1rem; }
        .yb-price-sheet[data-price-grid-shape="organic"] .yb-price-item { border-radius: 1.3rem .35rem 1.3rem .35rem; }
        .yb-price-sheet[data-price-grid-template="prestige"] .yb-price-header { background: var(--pg-dark); color: #fffbeb; }
        .yb-price-sheet[data-price-grid-template="prestige"] .yb-price-logo { filter: brightness(0) invert(1); }
        .yb-price-sheet[data-price-grid-template="digital"] .yb-price-header { background: linear-gradient(135deg, var(--pg-dark), #155e75); color: white; }
        .yb-price-sheet[data-price-grid-template="retro"] { border-width: 3px; }
        .yb-price-sheet[data-price-grid-template="retro"] .yb-price-header { border-bottom-style: dashed; }
        .yb-price-sheet[data-price-grid-template="minimal"] .yb-price-header { background: white; border-bottom-width: 1px; }
        .yb-price-sheet[data-price-grid-template="celebration"] .yb-price-header { background: linear-gradient(135deg, var(--pg-soft), #fce7f3); }
        @media (max-width: 640px) {
            .yb-price-sheet[data-price-grid-layout] .yb-price-items { grid-template-columns: 1fr; }
            .yb-price-sheet { min-height: 58rem; }
        }
    </style>
@endonce
