@php
    $sectionLabels = [
        'overview' => ['Aperçu', 'chart-pie'],
        'products' => ['Produits', 'cube'],
        'price_grid' => ['Grilles tarifaires', 'rectangle-stack'],
        'movements' => ['Mouvements', 'clock'],
        'physical' => ['Comptage', 'clipboard-document-check'],
        'operations' => ['Opérations', 'arrows-right-left'],
        'categories' => ['Référentiels', 'tag'],
        'analysis' => ['Analyses', 'chart-bar'],
    ];
    $reasonLabels = [
        'initial_stock' => 'Stock initial', 'supply' => 'Approvisionnement', 'adjustment' => 'Ajustement',
        'sale' => 'Vente', 'physical_inventory' => 'Inventaire physique',
    ];
    $unitTypeLabels = ['unit' => 'Unité', 'weight' => 'Poids', 'volume' => 'Volume', 'length' => 'Longueur', 'packaging' => 'Conditionnement'];
@endphp

<div
    class="space-y-6"
    x-data="{ toast: null, timer: null, notify(message, tone = 'success') { this.toast = { message, tone }; clearTimeout(this.timer); this.timer = setTimeout(() => this.toast = null, 3200) } }"
    @notify.window="notify($event.detail?.message || $event.detail?.[0] || $event.detail || 'Opération réussie')"
    @notify-error.window="notify($event.detail?.message || $event.detail?.[0] || $event.detail || 'Une erreur est survenue', 'error')"
>
    <div x-cloak x-show="toast" x-transition class="fixed right-4 top-20 z-[110] max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl" :class="toast?.tone === 'error' ? 'bg-rose-600' : 'bg-zinc-950 dark:bg-white dark:text-zinc-950'" x-text="toast?.message"></div>

    {{-- En-tête partagé : état du stock, export PDF et principales opérations d'inventaire. --}}
    <x-page-hero
        title="Inventaire"
        eyebrow="Stock en temps réel"
        description="Contrôlez le catalogue, les mouvements, les alertes et la valorisation du stock."
        icon="cube"
        badge-icon="archive-box"
        illustration="inventory"
    >
        <x-slot:actions>
            <flux:button icon="arrow-up-tray" wire:click="openImportModal" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Importer un catalogue Excel"><span class="max-sm:hidden">Importer Excel</span><span class="sm:hidden">Import</span></flux:button>
            @can('export inventory')
                <flux:button href="{{ route('inventory.stock.pdf', array_filter(['search' => $search ?: null, 'category' => $filterCategory ?: null, 'status' => $filterStockStatus ?: null, 'start' => $movementStartDate ?: null, 'end' => $movementEndDate ?: null])) }}" icon="arrow-down-tray" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Exporter le stock et ses mouvements en PDF"><span class="max-sm:hidden">Suivi du stock</span><span class="sm:hidden">PDF</span></flux:button>
            @endcan
            <flux:button icon="clipboard-document-check" wire:click="openPhysicalInventory" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Lancer un comptage"><span class="max-sm:hidden">Compter</span></flux:button>
            <flux:button icon="truck" wire:click="openOperation('supply')" class="bg-white/10! text-white! ring-white/20! hover:bg-white/20!" tooltip="Enregistrer une entrée"><span class="max-sm:hidden">Approvisionner</span></flux:button>
            <flux:button variant="primary" color="zinc" icon="plus" wire:click="openCreateProduct" class="bg-white! text-indigo-900! hover:bg-violet-50!">Nouvel article</flux:button>
        </x-slot:actions>
    </x-page-hero>

    <flux:card class="overflow-hidden p-1.5! shadow-sm">
        <div class="yb-scrollbar flex gap-1 overflow-x-auto" aria-label="Sections de l’inventaire">
            @foreach($sectionLabels as $section => [$label, $icon])
                <flux:button wire:click="setSection('{{ $section }}')" :variant="$activeSection === $section ? 'primary' : 'ghost'" :icon="$icon" class="shrink-0" :aria-current="$activeSection === $section ? 'page' : null">{{ $label }}</flux:button>
            @endforeach
        </div>
    </flux:card>

    @if($activeSection === 'overview')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card class="p-5!"><div class="flex items-center gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300"><flux:icon.cube class="size-5" /></span><div><flux:text>Produits physiques</flux:text><flux:heading size="xl">{{ number_format($stats['products']) }}</flux:heading></div></div></flux:card>
            <flux:card class="p-5!"><div class="flex items-center gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><flux:icon.scale class="size-5" /></span><div><flux:text>Quantité totale</flux:text><flux:heading size="xl">{{ number_format($stats['units'], 3, ',', ' ') }}</flux:heading></div></div></flux:card>
            <flux:card class="p-5!"><div class="flex items-center gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.banknotes class="size-5" /></span><div class="min-w-0"><flux:text>Valeur du stock</flux:text><flux:heading size="xl"><x-money :amount="$stats['value']" /></flux:heading></div></div></flux:card>
            <flux:card class="p-5!"><div class="flex items-center gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"><flux:icon.exclamation-triangle class="size-5" /></span><div><flux:text>Alertes / ruptures</flux:text><flux:heading size="xl">{{ $stats['alerts'] }} <span class="text-sm font-medium text-slate-500">/ {{ $stats['out'] }}</span></flux:heading></div></div></flux:card>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
            <flux:card class="overflow-hidden p-0!">
                <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><div><flux:heading>Articles à surveiller</flux:heading><flux:text size="sm">Sous le seuil d’alerte configuré.</flux:text></div><flux:button variant="ghost" wire:click="setSection('products')">Voir le catalogue</flux:button></div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($stockProducts->filter(fn($product) => (float) $product->stock_qty <= (float) $product->alert_qty)->take(8) as $product)
                        <div class="flex items-center gap-4 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ (float) $product->stock_qty <= 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300' }}"><flux:icon.cube class="size-5" /></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->sku }} · seuil {{ number_format($product->alert_qty, 3, ',', ' ') }} {{ $product->unitOfMeasure?->symbol }}</p></div>
                            <div class="text-right"><p class="font-black {{ (float) $product->stock_qty <= 0 ? 'text-rose-600' : 'text-amber-600' }}">{{ number_format($product->stock_qty, 3, ',', ' ') }} {{ $product->unitOfMeasure?->symbol }}</p><flux:button size="xs" variant="ghost" icon="truck" wire:click="openOperation('supply', {{ $product->id }})" tooltip="Approvisionner" aria-label="Approvisionner {{ $product->name }}" /></div>
                        </div>
                    @empty
                        <div class="p-6"><flux:callout color="emerald" icon="check-circle" heading="Stock sous contrôle" text="Aucun article n’est sous son seuil d’alerte." /></div>
                    @endforelse
                </div>
            </flux:card>

            <flux:card class="overflow-hidden p-0!">
                <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><div><flux:heading>Activité récente</flux:heading><flux:text size="sm">Derniers mouvements enregistrés.</flux:text></div><flux:button variant="ghost" wire:click="setSection('movements')">Historique</flux:button></div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($recentMovements->take(7) as $movement)
                        <div class="flex items-center gap-3 p-4"><span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $movement->type === 'in' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10' }}">{{ $movement->type === 'in' ? '+' : '−' }}</span><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $movement->product?->name ?? 'Article supprimé' }}</p><p class="truncate text-xs text-slate-500">{{ $reasonLabels[$movement->reason] ?? ucfirst($movement->reason) }} · {{ $movement->created_at->format('d/m H:i') }}</p></div><strong class="text-sm">{{ number_format($movement->quantity, 3, ',', ' ') }} {{ $movement->product?->unitOfMeasure?->symbol }}</strong></div>
                    @empty
                        <div class="p-6"><flux:callout icon="clock" heading="Aucun mouvement récent" text="Les opérations de stock apparaîtront ici." /></div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @elseif($activeSection === 'products')
        <flux:card class="overflow-hidden p-0!">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div class="flex items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.table-cells class="size-5" /></span><div><flux:heading>Catalogue Excel</flux:heading><flux:text size="sm">Importez, sauvegardez ou préparez votre liste de produits.</flux:text></div></div>
                    <div class="flex flex-wrap items-center gap-2">
                        @can('export inventory')
                            <flux:button size="sm" variant="outline" icon="document-arrow-down" wire:click="downloadProductTemplate" wire:loading.attr="disabled" wire:target="downloadProductTemplate">Modèle</flux:button>
                            <flux:button size="sm" variant="outline" color="emerald" icon="arrow-down-tray" wire:click="exportProducts" wire:loading.attr="disabled" wire:target="exportProducts">Exporter</flux:button>
                        @endcan
                        <flux:button size="sm" variant="primary" color="indigo" icon="arrow-up-tray" wire:click="openImportModal">Importer</flux:button>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(15rem,1fr)_repeat(3,minmax(9rem,.42fr))_auto]">
                    <flux:input type="search" wire:model.live.debounce.250ms="search" icon="magnifying-glass" clearable placeholder="Nom ou référence…" />
                    <flux:select wire:model.live="filterCategory"><flux:select.option value="">Toutes les catégories</flux:select.option>@foreach($categories as $category)<flux:select.option value="{{ $category->name }}">{{ $category->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:select wire:model.live="filterType"><flux:select.option value="">Tous les types</flux:select.option><flux:select.option value="product">Biens</flux:select.option><flux:select.option value="service">Services</flux:select.option><flux:select.option value="combo">Combos</flux:select.option></flux:select>
                    <flux:select wire:model.live="filterStockStatus"><flux:select.option value="">Tous les stocks</flux:select.option><flux:select.option value="in_stock">Disponible</flux:select.option><flux:select.option value="alert">Alerte</flux:select.option><flux:select.option value="out">Rupture</flux:select.option></flux:select>
                    <div class="flex items-center justify-end gap-2 md:col-span-2 xl:col-span-1">
                        <flux:button.group>
                            <flux:button icon="list-bullet" :variant="$productView === 'list' ? 'primary' : 'outline'" wire:click="setProductView('list')" tooltip="Affichage en liste" aria-label="Affichage en liste" />
                            <flux:button icon="squares-2x2" :variant="$productView === 'grid' ? 'primary' : 'outline'" wire:click="setProductView('grid')" tooltip="Affichage en grille" aria-label="Affichage en grille" />
                        </flux:button.group>
                        <flux:button variant="primary" icon="plus" wire:click="openCreateProduct">Ajouter</flux:button>
                    </div>
                </div>
            </div>
            @if($productView === 'list')
            <div class="px-5">
                <flux:table :paginate="$products" pagination:class="pb-4 pt-4" pagination:scroll-to="body" class="w-full">
                    <flux:table.columns>
                        <flux:table.row>
                            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortField === 'name' ? $sortDirection : null" wire:click="sortBy('name')"><x-table-heading icon="cube">Article</x-table-heading></flux:table.column>
                            <flux:table.column class="hidden lg:table-cell" sortable :sorted="$sortField === 'category'" :direction="$sortField === 'category' ? $sortDirection : null" wire:click="sortBy('category')"><x-table-heading icon="tag">Catégorie</x-table-heading></flux:table.column>
                            <flux:table.column class="hidden xl:table-cell"><x-table-heading icon="scale">Unité</x-table-heading></flux:table.column>
                            <flux:table.column align="end" sortable :sorted="$sortField === 'price'" :direction="$sortField === 'price' ? $sortDirection : null" wire:click="sortBy('price')"><x-table-heading icon="banknotes">Prix / coût</x-table-heading></flux:table.column>
                            <flux:table.column align="end" sortable :sorted="$sortField === 'stock_qty'" :direction="$sortField === 'stock_qty' ? $sortDirection : null" wire:click="sortBy('stock_qty')"><x-table-heading icon="archive-box">Stock</x-table-heading></flux:table.column>
                            <flux:table.column align="center" class="hidden md:table-cell"><x-table-heading icon="signal">Statut</x-table-heading></flux:table.column>
                            <flux:table.column align="end"><x-table-heading icon="ellipsis-horizontal">Actions</x-table-heading></flux:table.column>
                        </flux:table.row>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($products as $product)
                            <flux:table.row wire:key="inventory-product-{{ $product->id }}">
                                <flux:table.cell><div class="flex min-w-0 items-center gap-3"><span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">@if($product->image_path)<img src="{{ Storage::url($product->image_path) }}" alt="" class="size-full object-cover">@else<flux:icon.cube class="size-5 text-slate-400" />@endif</span><div class="min-w-0"><p class="truncate font-bold">{{ $product->name }}</p><p class="truncate font-mono text-xs text-slate-500">{{ $product->sku }}</p>@if($product->barcode)<p class="mt-0.5 flex items-center gap-1 truncate font-mono text-[11px] text-violet-600 dark:text-violet-300"><flux:icon.qr-code class="size-3" />{{ $product->barcode }}</p>@endif</div></div></flux:table.cell>
                                <flux:table.cell class="hidden lg:table-cell"><flux:badge>{{ $product->category }}</flux:badge></flux:table.cell>
                                <flux:table.cell class="hidden xl:table-cell">{{ $product->type === 'product' ? ($product->unitOfMeasure?->name ?? 'Non définie') : '—' }}</flux:table.cell>
                                <flux:table.cell align="end"><div class="flex items-center justify-end gap-2"><p class="font-bold"><x-money :amount="$product->price" /></p><flux:badge size="sm" :color="$product->tax_included ? 'emerald' : 'amber'">{{ $product->tax_included ? 'TTC' : 'HT' }}</flux:badge></div><p class="text-xs text-zinc-500">Coût <x-money :amount="$product->cost" /></p></flux:table.cell>
                                <flux:table.cell align="end">@if($product->type !== 'product')<span class="text-slate-400">{{ $product->type === 'combo' ? 'Combo' : 'Service' }}</span>@else<p class="font-black {{ (float) $product->stock_qty <= (float) $product->alert_qty ? 'text-amber-600' : '' }}">{{ number_format($product->stock_qty, 3, ',', ' ') }} {{ $product->unitOfMeasure?->symbol }}</p><p class="text-xs text-slate-500">Alerte {{ number_format($product->alert_qty, 3, ',', ' ') }}</p>@endif</flux:table.cell>
                                <flux:table.cell align="center" class="hidden md:table-cell"><flux:badge :color="$product->is_active ? 'emerald' : 'zinc'">{{ $product->is_active ? 'Actif' : 'Inactif' }}</flux:badge></flux:table.cell>
                                <flux:table.cell align="end">@include('livewire.inventory-product-actions', ['product' => $product, 'compact' => true])</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row><flux:table.cell colspan="7"><flux:callout icon="cube" heading="Aucun article" text="Créez votre premier produit ou modifiez les filtres." /></flux:table.cell></flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            @else
                <div class="p-5">
                    <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @forelse($products as $product)
                            <flux:card wire:key="inventory-product-grid-{{ $product->id }}" class="flex min-w-0 flex-col overflow-hidden p-0!">
                                <div class="relative aspect-[16/8] overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    @if($product->image_path)
                                        <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="size-full object-cover">
                                    @else
                                        <div class="flex size-full items-center justify-center"><flux:icon.cube class="size-12 text-slate-300 dark:text-slate-600" /></div>
                                    @endif
                                    <div class="absolute left-3 top-3"><flux:badge :color="$product->is_active ? 'emerald' : 'zinc'">{{ $product->is_active ? 'Actif' : 'Inactif' }}</flux:badge></div>
                                    <div class="absolute right-3 top-3"><flux:badge color="zinc">{{ ['product' => 'Bien', 'service' => 'Service', 'combo' => 'Combo'][$product->type] ?? ucfirst($product->type) }}</flux:badge></div>
                                </div>
                                <div class="flex flex-1 flex-col p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0"><flux:heading class="truncate">{{ $product->name }}</flux:heading><p class="mt-1 font-mono text-xs text-slate-500">{{ $product->sku }}</p>@if($product->barcode)<p class="mt-1 flex items-center gap-1 truncate font-mono text-[11px] text-violet-600 dark:text-violet-300"><flux:icon.qr-code class="size-3" />{{ $product->barcode }}</p>@endif</div>
                                        <div class="shrink-0 text-right"><strong class="block text-lg"><x-money :amount="$product->price" /></strong><flux:badge size="sm" :color="$product->tax_included ? 'emerald' : 'amber'">{{ $product->tax_included ? 'TTC' : 'HT' }}</flux:badge></div>
                                    </div>
                                    <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/70">
                                        <div><span class="block text-xs text-slate-500">Catégorie</span><strong class="block truncate">{{ $product->category }}</strong></div>
                                        <div class="text-right"><span class="block text-xs text-slate-500">{{ $product->type === 'product' ? 'Stock' : 'Type' }}</span><strong class="{{ $product->type === 'product' && (float) $product->stock_qty <= (float) $product->alert_qty ? 'text-amber-600' : '' }}">{{ $product->type === 'product' ? number_format($product->stock_qty, 3, ',', ' ').' '.($product->unitOfMeasure?->symbol ?? '') : ($product->type === 'combo' ? 'Combo' : 'Service') }}</strong></div>
                                    </div>
                                    <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-700">@include('livewire.inventory-product-actions', ['product' => $product, 'compact' => false])</div>
                                </div>
                            </flux:card>
                        @empty
                            <div class="sm:col-span-2 2xl:col-span-3"><flux:callout icon="cube" heading="Aucun article" text="Créez votre premier produit ou modifiez les filtres." /></div>
                        @endforelse
                    </div>
                    <flux:pagination :paginator="$products" class="mt-5" scroll-to="body" />
                </div>
            @endif
        </flux:card>
    @elseif($activeSection === 'price_grid')
        @include('livewire.inventory-price-grids')
    @elseif($activeSection === 'categories')
        <div class="grid gap-5 xl:grid-cols-2">
            <flux:card class="overflow-hidden p-0!">
                <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><div><flux:heading>Catégories</flux:heading><flux:text size="sm">Structure du catalogue et préfixes SKU.</flux:text></div><flux:button variant="primary" icon="plus" wire:click="openCategoryModal">Catégorie</flux:button></div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">@foreach($categories as $category)<div class="flex items-center gap-3 p-4"><span class="flex size-10 items-center justify-center rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-500/10"><flux:icon.tag class="size-5" /></span><div class="min-w-0 flex-1"><p class="font-bold">{{ $category->name }}</p><p class="text-xs text-slate-500">Code {{ $category->code }} · {{ $categoryCounts[$category->name] ?? 0 }} article(s)</p></div><flux:badge :color="$category->is_active ? 'emerald' : 'zinc'">{{ $category->is_active ? 'Active' : 'Inactive' }}</flux:badge><flux:button size="sm" variant="primary" color="blue" icon="pencil-square" wire:click="openCategoryModal({{ $category->id }})" tooltip="Modifier" /><flux:button size="sm" variant="danger" icon="trash" wire:click="deleteCategory({{ $category->id }})" tooltip="Supprimer" /></div>@endforeach</div>
            </flux:card>
            <flux:card class="overflow-hidden p-0!">
                <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><div><flux:heading>Unités de mesure</flux:heading><flux:text size="sm">Unités, poids, volumes et conditionnements.</flux:text></div><flux:button variant="primary" icon="plus" wire:click="openUnitModal">Unité</flux:button></div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">@foreach($units as $unit)<div class="flex items-center gap-3 p-4"><span class="flex size-10 items-center justify-center rounded-xl bg-blue-50 font-black text-blue-700 dark:bg-blue-500/10">{{ $unit->symbol }}</span><div class="min-w-0 flex-1"><p class="font-bold">{{ $unit->name }}</p><p class="text-xs text-slate-500">{{ $unitTypeLabels[$unit->type] ?? ucfirst($unit->type) }} · {{ $unit->products_count }} article(s)</p></div><flux:badge :color="$unit->is_active ? 'emerald' : 'zinc'">{{ $unit->is_active ? 'Active' : 'Inactive' }}</flux:badge><flux:button size="sm" variant="primary" color="blue" icon="pencil-square" wire:click="openUnitModal({{ $unit->id }})" tooltip="Modifier" /><flux:button size="sm" variant="danger" icon="trash" wire:click="deleteUnit({{ $unit->id }})" tooltip="Supprimer" /></div>@endforeach</div>
            </flux:card>
        </div>
    @elseif($activeSection === 'operations')
        <div class="grid gap-5 lg:grid-cols-3">
            <flux:card class="flex flex-col p-6!"><span class="flex size-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10"><flux:icon.truck class="size-6" /></span><flux:heading size="lg" class="mt-5">Approvisionnement</flux:heading><flux:text class="mt-2 flex-1">Enregistrez une réception fournisseur, mettez à jour le coût moyen et générez l’écriture comptable.</flux:text><flux:button variant="primary" class="mt-6" wire:click="openOperation('supply')">Nouvel approvisionnement</flux:button></flux:card>
            <flux:card class="flex flex-col p-6!"><span class="flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 dark:bg-amber-500/10"><flux:icon.arrows-right-left class="size-6" /></span><flux:heading size="lg" class="mt-5">Ajustement</flux:heading><flux:text class="mt-2 flex-1">Corrigez une entrée ou une sortie pour perte, casse, retour ou correction administrative.</flux:text><flux:button class="mt-6" wire:click="openOperation('adjustment')">Ajuster le stock</flux:button></flux:card>
            <flux:card class="flex flex-col p-6!"><span class="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-500/10"><flux:icon.clipboard-document-check class="size-6" /></span><flux:heading size="lg" class="mt-5">Inventaire physique</flux:heading><flux:text class="mt-2 flex-1">Comparez les quantités comptées aux quantités théoriques et journalisez chaque écart.</flux:text><flux:button class="mt-6" wire:click="openPhysicalInventory">Démarrer le comptage</flux:button></flux:card>
        </div>
        <flux:card class="overflow-hidden p-0!"><div class="border-b border-slate-200 p-5 dark:border-slate-800"><flux:heading>Historique des opérations</flux:heading><flux:text size="sm">Approvisionnements, ajustements et stocks initiaux.</flux:text></div>@include('livewire.inventory-movements-table', ['movementRows' => $operationMovements, 'movementPaginator' => $operationMovements, 'reasonLabels' => $reasonLabels])</flux:card>
    @elseif($activeSection === 'movements')
        <flux:card class="overflow-hidden p-0!">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800"><div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"><flux:input type="date" label="Du" wire:model.live="movementStartDate" /><flux:input type="date" label="Au" wire:model.live="movementEndDate" /><flux:select label="Type" wire:model.live="movementType"><flux:select.option value="">Tous</flux:select.option><flux:select.option value="in">Entrées</flux:select.option><flux:select.option value="out">Sorties</flux:select.option></flux:select><flux:select label="Motif" wire:model.live="movementReason"><flux:select.option value="">Tous</flux:select.option>@foreach($reasonLabels as $reason => $label)<flux:select.option value="{{ $reason }}">{{ $label }}</flux:select.option>@endforeach</flux:select><flux:select label="Produit" wire:model.live="movementProductId"><flux:select.option value="">Tous</flux:select.option>@foreach($stockProducts as $product)<flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>@endforeach</flux:select></div></div>
            @include('livewire.inventory-movements-table', ['movementRows' => $movements, 'movementPaginator' => $movements, 'reasonLabels' => $reasonLabels])
        </flux:card>
    @elseif($activeSection === 'physical')
        <div class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
            <flux:card class="p-7!"><span class="flex size-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-500/10"><flux:icon.clipboard-document-check class="size-7" /></span><flux:heading size="xl" class="mt-6">Inventaire physique</flux:heading><flux:text class="mt-3">Saisissez les quantités réellement comptées. Seuls les écarts génèrent un mouvement, avec la quantité avant et après correction.</flux:text><div class="mt-6 space-y-3 text-sm"><div class="flex items-center gap-2"><flux:icon.check-circle class="size-5 text-emerald-500" /> Quantités décimales acceptées</div><div class="flex items-center gap-2"><flux:icon.check-circle class="size-5 text-emerald-500" /> Traçabilité par référence</div><div class="flex items-center gap-2"><flux:icon.check-circle class="size-5 text-emerald-500" /> Aucun impact comptable automatique</div></div><flux:button variant="primary" class="mt-7 w-full" icon="play" wire:click="openPhysicalInventory">Commencer l’inventaire</flux:button></flux:card>
            <flux:card class="overflow-hidden p-0!"><div class="border-b border-slate-200 p-5 dark:border-slate-800"><flux:heading>Écarts constatés</flux:heading><flux:text size="sm">Historique paginé des corrections issues des comptages.</flux:text></div>@include('livewire.inventory-movements-table', ['movementRows' => $physicalMovements, 'movementPaginator' => $physicalMovements, 'reasonLabels' => $reasonLabels])</flux:card>
        </div>
    @elseif($activeSection === 'analysis')
        @php
            $maxMovement = max(1, (float) ($topMovements->max('movement_total') ?? 1));
            $maxTimelineValue = max(1, (float) max($movementTimeline->max('in') ?? 0, $movementTimeline->max('out') ?? 0));
        @endphp
        <div class="grid gap-4 sm:grid-cols-3"><flux:card class="p-5!"><flux:text>Entrées sur la période</flux:text><flux:heading size="xl" class="mt-2 text-emerald-600">+{{ number_format($analysisIn, 3, ',', ' ') }}</flux:heading></flux:card><flux:card class="p-5!"><flux:text>Sorties sur la période</flux:text><flux:heading size="xl" class="mt-2 text-rose-600">−{{ number_format($analysisOut, 3, ',', ' ') }}</flux:heading></flux:card><flux:card class="p-5!"><flux:text>Flux net</flux:text><flux:heading size="xl" class="mt-2">{{ number_format($analysisIn - $analysisOut, 3, ',', ' ') }}</flux:heading></flux:card></div>
        <flux:card class="overflow-hidden p-0!">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div><flux:heading size="lg">Évolution des mouvements</flux:heading><flux:text size="sm">Entrées et sorties par jour sur la période sélectionnée.</flux:text></div>
                <div class="flex items-center gap-4 text-xs font-medium text-slate-500"><span class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-emerald-500"></span>Entrées</span><span class="flex items-center gap-2"><span class="size-2.5 rounded-full bg-rose-500"></span>Sorties</span></div>
            </div>
            <div class="yb-scrollbar overflow-x-auto p-5">
                @if($movementTimeline->isNotEmpty())
                    <div class="grid h-64 items-end gap-3" style="grid-template-columns: repeat({{ $movementTimeline->count() }}, minmax(3.5rem, 1fr)); min-width: {{ max(100, $movementTimeline->count() * 7) }}%;" role="img" aria-label="Graphique des entrées et sorties de stock">
                        @foreach($movementTimeline as $point)
                            <div class="group flex h-full min-w-0 flex-col items-center justify-end gap-2" title="{{ $point['label'] }} · Entrées {{ number_format($point['in'], 3, ',', ' ') }} · Sorties {{ number_format($point['out'], 3, ',', ' ') }}">
                                <div class="flex h-48 w-full items-end justify-center gap-1 rounded-xl bg-slate-50 px-2 pt-4 dark:bg-slate-800/60">
                                    <div class="w-2/5 rounded-t-md bg-emerald-500 transition-[height,background-color] duration-500 group-hover:bg-emerald-400" style="height: {{ $point['in'] > 0 ? max(3, ($point['in'] / $maxTimelineValue) * 100) : 0 }}%;" aria-label="Entrées {{ number_format($point['in'], 3, ',', ' ') }}"></div>
                                    <div class="w-2/5 rounded-t-md bg-rose-500 transition-[height,background-color] duration-500 group-hover:bg-rose-400" style="height: {{ $point['out'] > 0 ? max(3, ($point['out'] / $maxTimelineValue) * 100) : 0 }}%;" aria-label="Sorties {{ number_format($point['out'], 3, ',', ' ') }}"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-500">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex min-h-56 items-center justify-center"><flux:callout icon="chart-bar" heading="Aucune donnée à tracer" text="Élargissez la période ou enregistrez un mouvement de stock." /></div>
                @endif
            </div>
        </flux:card>
        <div class="grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
            <flux:card class="p-5!"><flux:heading>Articles les plus mouvementés</flux:heading><div class="mt-5 space-y-4">@forelse($topMovements as $movement)<div><div class="mb-1.5 flex justify-between gap-3 text-sm"><span class="truncate font-bold">{{ $movement->product?->name ?? 'Article supprimé' }}</span><span>{{ number_format($movement->movement_total, 3, ',', ' ') }} {{ $movement->product?->unitOfMeasure?->symbol }}</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-purple-600" style="width: {{ max(3, ($movement->movement_total / $maxMovement) * 100) }}%"></div></div></div>@empty<flux:callout icon="chart-bar" heading="Pas encore de données" text="Les mouvements de la période alimenteront cette analyse." />@endforelse</div></flux:card>
            <flux:card class="overflow-hidden p-0!"><div class="border-b border-slate-200 p-5 dark:border-slate-800"><flux:heading>Répartition par motif</flux:heading></div><div class="divide-y divide-slate-200 dark:divide-slate-800">@forelse($reasonAnalysis as $reason)<div class="flex items-center justify-between p-4"><div><p class="font-bold">{{ $reasonLabels[$reason->reason] ?? ucfirst($reason->reason) }}</p><p class="text-xs text-slate-500">{{ $reason->operations }} opération(s)</p></div><strong>{{ number_format($reason->quantity_total, 3, ',', ' ') }}</strong></div>@empty<div class="p-5"><flux:callout icon="chart-pie" heading="Aucune donnée" /></div>@endforelse</div></flux:card>
        </div>
    @endif

    @if($showImportModal)
        <flux:modal wire:model="showImportModal" class="w-full max-w-4xl">
            <div wire:loading.flex wire:target="prepareProductImport,importProducts" class="fixed inset-0 z-[200] items-center justify-center bg-slate-950/45 p-5 backdrop-blur-sm" role="status" aria-live="polite">
                <flux:card class="w-full max-w-sm p-6! text-center shadow-2xl">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><flux:icon.arrow-path class="size-7 animate-spin" /></span>
                    <flux:heading class="mt-4" wire:loading wire:target="prepareProductImport">Analyse du catalogue…</flux:heading>
                    <flux:heading class="mt-4" wire:loading wire:target="importProducts">Enregistrement des produits…</flux:heading>
                    <flux:text size="sm" class="mt-2">Veuillez patienter sans fermer cette fenêtre.</flux:text>
                </flux:card>
            </div>

            <div class="flex items-start gap-4 border-b border-slate-200 pb-5 pr-10 dark:border-slate-700">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300"><flux:icon.table-cells class="size-6" /></span>
                <div class="min-w-0"><flux:heading size="xl">Importer une liste de produits</flux:heading><flux:text class="mt-1">Analyse sécurisée du fichier avant toute modification du catalogue.</flux:text></div>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-2" aria-label="Étapes de l’importation">
                @foreach([['upload', '1', 'Fichier'], ['preview', '2', 'Contrôle'], ['complete', '3', 'Terminé']] as [$step, $number, $label])
                    @php($stepActive = array_search($importStep, ['upload', 'preview', 'complete'], true) >= array_search($step, ['upload', 'preview', 'complete'], true))
                    <div class="rounded-xl px-3 py-2.5 text-center text-sm font-semibold {{ $stepActive ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200' : 'bg-slate-50 text-slate-400 dark:bg-slate-800' }}"><span class="mr-1 inline-flex size-5 items-center justify-center rounded-full {{ $stepActive ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-700' }}">{{ $number }}</span>{{ $label }}</div>
                @endforeach
            </div>

            @if($importStep === 'upload')
                <div class="mt-6 grid gap-5 lg:grid-cols-[1fr_.72fr]">
                    <flux:card class="p-6!" x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-progress="progress = $event.detail.progress" x-on:livewire-upload-finish="progress = 100; setTimeout(() => uploading = false, 350)" x-on:livewire-upload-error="uploading = false">
                        <div class="flex min-h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/50 p-6 text-center dark:border-indigo-900 dark:bg-indigo-500/5">
                            <span class="flex size-14 items-center justify-center rounded-2xl bg-white text-indigo-600 shadow-sm dark:bg-zinc-800 dark:text-indigo-300"><flux:icon.arrow-up-tray class="size-7" /></span>
                            <flux:heading class="mt-4">Sélectionnez votre fichier</flux:heading>
                            <flux:text size="sm" class="mt-1 max-w-md">Excel XLSX, XLS ou CSV · 2 000 lignes maximum · 10 Mo</flux:text>
                            <div class="mt-5 w-full max-w-lg"><flux:input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" label="Fichier produits" /></div>
                            <div x-cloak x-show="uploading" class="mt-4 w-full max-w-lg" aria-live="polite">
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm font-semibold text-indigo-700 dark:text-indigo-300"><span class="flex items-center gap-2"><flux:icon.arrow-path class="size-4 animate-spin" /> Téléversement en cours</span><span x-text="`${progress}%`"></span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-950"><div class="h-full rounded-full bg-indigo-600 transition-[width] duration-300" x-bind:style="`width: ${progress}%`"></div></div>
                            </div>
                            <flux:error name="importFile" />
                        </div>
                        <div class="mt-5 flex flex-col gap-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-800/70 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="text-sm font-bold">Mise à jour par SKU</p><p class="text-xs text-slate-500">Le stock existant reste inchangé pour préserver sa traçabilité.</p></div>
                            <flux:switch wire:model="updateExistingProducts" label="Mettre à jour les SKU existants" />
                        </div>
                    </flux:card>
                    <flux:card class="p-6!">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.document-arrow-down class="size-6" /></span>
                        <flux:heading class="mt-5">Commencez avec le bon format</flux:heading>
                        <flux:text size="sm" class="mt-2">Le modèle contient les colonnes attendues, deux exemples et une feuille d’instructions.</flux:text>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            @foreach([['SKU', 'Automatique'], ['Type', 'Bien'], ['Prix & coût', '0'], ['TVA', '0 %'], ['Mode taxe', $this->defaultTaxModeLabel()], ['Stock initial', '0'], ['Seuil d’alerte', '5'], ['Actif', 'Oui'], ['Notes internes', 'Vide']] as [$label, $default])
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-800/70"><span class="block text-xs text-slate-500">{{ $label }}</span><strong>{{ $default }}</strong></div>
                            @endforeach
                        </div>
                        <div class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300"><p class="flex gap-2"><flux:icon.check-circle class="size-5 shrink-0 text-emerald-500" /> Catégories absentes créées automatiquement</p><p class="flex gap-2"><flux:icon.check-circle class="size-5 shrink-0 text-emerald-500" /> Contrôle des doublons et valeurs</p><p class="flex gap-2"><flux:icon.check-circle class="size-5 shrink-0 text-emerald-500" /> Aperçu avant enregistrement</p></div>
                        <flux:button class="mt-6 w-full" variant="outline" color="emerald" icon="arrow-down-tray" wire:click="downloadProductTemplate">Télécharger le modèle Excel</flux:button>
                    </flux:card>
                </div>
                <div class="mt-6 flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-slate-700"><flux:button variant="ghost" wire:click="$set('showImportModal', false)">Annuler</flux:button><flux:button variant="primary" color="indigo" icon="magnifying-glass" wire:click="prepareProductImport" wire:loading.attr="disabled" wire:target="prepareProductImport,importFile"><span wire:loading.remove wire:target="prepareProductImport">Analyser le fichier</span><span wire:loading wire:target="prepareProductImport">Analyse…</span></flux:button></div>
            @elseif($importStep === 'preview')
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([['Lignes lues', $importSummary['total'] ?? 0, 'table-cells', 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200'], ['À créer', $importSummary['create'] ?? 0, 'plus-circle', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'], ['À mettre à jour', $importSummary['update'] ?? 0, 'arrow-path', 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'], ['À corriger', $importSummary['errors'] ?? 0, 'exclamation-triangle', 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300']] as [$label, $value, $icon, $tone])
                        <flux:card class="p-4!"><div class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-xl {{ $tone }}"><flux:icon :name="$icon" class="size-5" /></span><div><flux:text size="sm">{{ $label }}</flux:text><flux:heading size="xl">{{ $value }}</flux:heading></div></div></flux:card>
                    @endforeach
                </div>
                @if($importErrors !== [])
                    <flux:callout class="mt-5" color="rose" icon="exclamation-triangle" heading="Certaines lignes seront ignorées" text="Corrigez le fichier puis relancez l’analyse pour importer toutes les lignes." />
                    <div class="yb-scrollbar mt-3 max-h-36 overflow-y-auto rounded-xl border border-rose-200 dark:border-rose-900"><div class="divide-y divide-rose-100 dark:divide-rose-900">@foreach($importErrors as $error)<div class="grid gap-1 px-4 py-3 text-sm sm:grid-cols-[5rem_9rem_1fr]"><strong>Ligne {{ $error['line'] }}</strong><span class="font-mono text-xs">{{ $error['sku'] }}</span><span class="text-rose-700 dark:text-rose-300">{{ implode(' ', $error['messages']) }}</span></div>@endforeach</div></div>
                @endif
                <flux:card class="mt-5 overflow-hidden p-0!">
                    <div class="flex items-center justify-between border-b border-slate-200 p-4 dark:border-slate-700"><div><flux:heading>Aperçu des lignes valides</flux:heading><flux:text size="sm">Les 25 premières lignes sont affichées.</flux:text></div><flux:badge color="emerald">{{ $importSummary['valid'] ?? 0 }} valide(s)</flux:badge></div>
                    <div class="yb-scrollbar overflow-x-auto">
                        <flux:table class="w-full">
                            <flux:table.columns>
                                <flux:table.column>Ligne</flux:table.column><flux:table.column>Article</flux:table.column><flux:table.column>Type</flux:table.column><flux:table.column>Catégorie</flux:table.column><flux:table.column align="end">Prix</flux:table.column><flux:table.column align="end">Stock</flux:table.column><flux:table.column>Action</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse($importPreview as $row)
                                    <flux:table.row wire:key="import-preview-{{ $row['line'] }}-{{ $row['sku'] }}">
                                        <flux:table.cell class="text-slate-500">{{ $row['line'] }}</flux:table.cell>
                                        <flux:table.cell><strong class="block">{{ $row['name'] }}</strong><span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate-500">{{ $row['sku'] }} @if($row['sku_generated'] ?? false)<flux:badge size="sm" color="indigo">Auto</flux:badge>@endif</span></flux:table.cell>
                                        <flux:table.cell>{{ $row['type'] === 'product' ? 'Bien' : 'Service' }}</flux:table.cell>
                                        <flux:table.cell>{{ $row['category'] }}</flux:table.cell>
                                        <flux:table.cell align="end"><x-money :amount="$row['price']" /></flux:table.cell>
                                        <flux:table.cell align="end">{{ number_format($row['stock'], 3, ',', ' ') }}</flux:table.cell>
                                        <flux:table.cell><flux:badge :color="$row['existing'] ? 'blue' : 'emerald'">{{ $row['existing'] ? 'Mise à jour' : 'Création' }}</flux:badge></flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row><flux:table.cell colspan="7"><flux:callout color="rose" icon="x-circle" heading="Aucune ligne valide" text="Corrigez les erreurs dans votre fichier." /></flux:table.cell></flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </flux:card>
                <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 dark:border-slate-700 sm:flex-row sm:justify-between"><flux:button variant="ghost" icon="arrow-left" wire:click="resetProductImport">Changer de fichier</flux:button><div class="flex justify-end gap-2"><flux:button variant="ghost" wire:click="$set('showImportModal', false)">Annuler</flux:button><flux:button variant="primary" color="emerald" icon="check" wire:click="importProducts" :disabled="($importSummary['valid'] ?? 0) === 0" wire:loading.attr="disabled" wire:target="importProducts"><span wire:loading.remove wire:target="importProducts">Confirmer l’import</span><span wire:loading wire:target="importProducts">Importation…</span></flux:button></div></div>
            @else
                <div class="mt-8 flex flex-col items-center py-8 text-center"><span class="flex size-20 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><flux:icon.check-circle class="size-11" /></span><flux:heading size="xl" class="mt-5">Catalogue importé avec succès</flux:heading><flux:text class="mt-2">Référence {{ $importResult['reference'] ?? '—' }}</flux:text><div class="mt-6 grid w-full max-w-lg grid-cols-2 gap-3"><flux:card class="p-5!"><flux:text>Produits créés</flux:text><flux:heading size="xl" class="mt-1 text-emerald-600">{{ $importResult['created'] ?? 0 }}</flux:heading></flux:card><flux:card class="p-5!"><flux:text>Produits mis à jour</flux:text><flux:heading size="xl" class="mt-1 text-blue-600">{{ $importResult['updated'] ?? 0 }}</flux:heading></flux:card></div><div class="mt-7 flex gap-2"><flux:button variant="outline" wire:click="openImportModal">Nouvel import</flux:button><flux:button variant="primary" color="indigo" wire:click="$set('showImportModal', false)">Fermer</flux:button></div></div>
            @endif
        </flux:modal>
    @endif

    @if($showProductModal)
        <flux:modal wire:model="showProductModal" variant="flyout" position="right" class="w-full md:w-[42rem]">
            <div class="flex items-start gap-4 border-b border-slate-200 pb-5 pr-10 dark:border-slate-700">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><flux:icon.cube class="size-5" /></span>
                <div><flux:heading size="lg">{{ $productId ? 'Modifier l’article' : 'Nouvel article' }}</flux:heading><flux:text class="mt-1">Le catalogue et les informations de vente sont mis à jour immédiatement.</flux:text></div>
            </div>
            <form wire:submit="saveProduct" class="mt-6 space-y-5">
                <flux:card class="p-5!">
                    <div class="grid gap-5 sm:grid-cols-[9rem_1fr] sm:items-center">
                        <div class="aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                            @if($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="Aperçu de la nouvelle image" class="size-full object-cover">
                            @elseif($currentImagePath)
                                <img src="{{ Storage::url($currentImagePath) }}" alt="Image actuelle de l’article" class="size-full object-cover">
                            @else
                                <div class="flex size-full flex-col items-center justify-center gap-2 text-slate-400"><flux:icon.photo class="size-8" /><span class="text-xs">Aperçu image</span></div>
                            @endif
                        </div>
                        <div class="space-y-4"><div><flux:heading>Média et visibilité</flux:heading><flux:text size="sm" class="mt-1">Ajoutez une image nette pour faciliter l’identification de l’article.</flux:text></div><flux:input label="Image de l’article" type="file" accept="image/*" wire:model.live="image" /><flux:switch wire:model="isActive" label="Article actif" description="Disponible dans le catalogue de vente" /></div>
                    </div>
                </flux:card>

                <flux:card class="p-5!">
                    <flux:fieldset legend="Informations générales" description="Identité et classement de l’article.">
                        <flux:input label="Nom de l’article" wire:model="name" autofocus />
                        <flux:field>
                            <flux:label>Référence SKU</flux:label>
                            <flux:input.group>
                                <flux:input wire:model="sku" placeholder="PRD-BAR-001" />
                                <flux:button type="button" icon="arrow-path" wire:click="generateSku(true)">Générer</flux:button>
                            </flux:input.group>
                            <flux:error name="sku" />
                        </flux:field>
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_13rem] lg:items-end">
                            <flux:field>
                                <flux:label>Code-barres / QR produit</flux:label>
                                <flux:input.group>
                                    <flux:input wire:model.live.debounce.250ms="barcode" inputmode="text" autocomplete="off" placeholder="Scanner, saisir ou générer" icon="qr-code" />
                                    <flux:button type="button" icon="sparkles" wire:click="generateBarcode">Générer EAN-13</flux:button>
                                </flux:input.group>
                                <flux:error name="barcode" />
                                <flux:description>Compatible avec les lecteurs USB/Bluetooth configurés en mode clavier.</flux:description>
                            </flux:field>
                            <div class="min-h-24 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700">
                                @if(filled($barcode))
                                    {!! \App\Support\DocumentCode::render('barcode', $barcode, 96) !!}
                                @else
                                    <div class="flex min-h-20 items-center justify-center gap-2 text-xs text-slate-400"><flux:icon.bars-3 class="size-5" />Aperçu du code</div>
                                @endif
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <flux:select label="Type de produit" wire:model.live="type"><flux:select.option value="product">Biens</flux:select.option><flux:select.option value="service">Service</flux:select.option><flux:select.option value="combo">Combo</flux:select.option></flux:select>
                            <flux:select label="Catégorie" wire:model.live="categoryName">@foreach($categories->where('is_active', true) as $category)<flux:select.option value="{{ $category->name }}">{{ $category->name }}</flux:select.option>@endforeach</flux:select>
                            <flux:select label="Unité de mesure" wire:model="unitOfMeasureId" :disabled="$type !== 'product'"><flux:select.option value="">Aucune</flux:select.option>@foreach($units->where('is_active', true) as $unit)<flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</flux:select.option>@endforeach</flux:select>
                        </div>
                    </flux:fieldset>
                </flux:card>

                @if($type === 'combo')
                    @php($comboTotal = collect($comboItems)->sum(fn($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)))
                    <flux:card class="p-5!">
                        <div class="flex items-start justify-between gap-4">
                            <div><flux:heading>Composition du Combo</flux:heading><flux:text size="sm" class="mt-1">Sélectionnez les articles inclus et leur quantité.</flux:text></div>
                            <flux:button type="button" size="sm" variant="primary" color="violet" icon="plus" wire:click="addComboItem">Ajouter</flux:button>
                        </div>
                        <div class="mt-5 space-y-3">
                            @forelse($comboItems as $index => $comboItem)
                                <div wire:key="combo-item-{{ $index }}" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                    <div class="grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_7rem_9rem_auto]">
                                        <flux:select label="Article" wire:model.live="comboItems.{{ $index }}.product_id">
                                            <flux:select.option value="">Sélectionner…</flux:select.option>
                                            @foreach($comboProducts as $comboProduct)<flux:select.option value="{{ $comboProduct->id }}">{{ $comboProduct->name }}</flux:select.option>@endforeach
                                        </flux:select>
                                        <flux:input label="Quantité" type="number" step="0.001" min="0.001" wire:model.live.debounce.250ms="comboItems.{{ $index }}.quantity" />
                                        <flux:field>
                                            <flux:label>Prix par défaut</flux:label>
                                            <flux:input.group><flux:input readonly :value="\App\Support\DisplayCurrency::format((float) ($comboItem['unit_price'] ?? 0), null, false)" /><flux:input.group.suffix>{{ \App\Support\DisplayCurrency::code() }}</flux:input.group.suffix></flux:input.group>
                                        </flux:field>
                                        <flux:button type="button" variant="danger" icon="trash" wire:click="removeComboItem({{ $index }})" tooltip="Retirer l’article" />
                                    </div>
                                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-sm dark:border-slate-800"><span class="text-slate-500">Sous-total</span><strong><x-money :amount="(float) ($comboItem['quantity'] ?? 0) * (float) ($comboItem['unit_price'] ?? 0)" /></strong></div>
                                </div>
                            @empty
                                <flux:callout icon="squares-plus" heading="Combo vide" text="Ajoutez au moins un article pour calculer son prix." />
                            @endforelse
                        </div>
                        <div class="mt-5 flex items-center justify-between rounded-2xl bg-violet-50 p-4 text-violet-950 dark:bg-violet-500/10 dark:text-violet-200"><div><p class="text-xs font-semibold uppercase tracking-wide opacity-70">Prix total du combo</p><p class="text-xs opacity-70">Calcul automatique</p></div><strong class="text-2xl"><x-money :amount="$comboTotal" /></strong></div>
                        <flux:error name="comboItems" />
                    </flux:card>
                @endif

                <flux:card class="p-5!">
                    <flux:fieldset legend="Tarification et taxes" description="Prix appliqués lors de la vente.">
                        @if($type !== 'combo')
                            <div class="mb-5 flex flex-col gap-3 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/60 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0"><div class="flex items-center gap-2"><flux:icon.banknotes class="size-5 text-violet-600" /><flux:heading size="sm">Tarification en {{ $priceEntryCurrency }}</flux:heading></div><flux:text size="sm" class="mt-1">Les montants sont enregistrés sans conversion dans la devise de base de l’entreprise.</flux:text></div>
                                <div class="flex shrink-0 items-center gap-2"><flux:badge color="zinc">{{ \App\Support\DisplayCurrency::rateLabel() }}</flux:badge><flux:badge color="violet">Devise unique</flux:badge></div>
                            </div>
                        @endif
                        <div class="grid gap-4 {{ $type === 'combo' ? 'sm:grid-cols-1' : 'sm:grid-cols-3' }}">
                            @if($type !== 'combo')
                            <flux:field>
                                <flux:label>Prix de vente</flux:label>
                                <flux:input.group><flux:input wire:model.live.debounce.350ms="price" inputmode="decimal" autocomplete="off" placeholder="0,00" aria-label="Prix de vente en {{ $priceEntryCurrency }}" /><flux:input.group.suffix>{{ \App\Support\DisplayCurrency::symbol($priceEntryCurrency) }}</flux:input.group.suffix></flux:input.group>
                                <flux:error name="price" />
                                <flux:text size="sm" class="mt-1">Aperçu ticket : {{ $this->priceEntryEquivalent($price) }}</flux:text>
                            </flux:field>
                            <flux:field>
                                <flux:label>Coût unitaire</flux:label>
                                <flux:input.group><flux:input wire:model.live.debounce.350ms="cost" inputmode="decimal" autocomplete="off" placeholder="0,00" aria-label="Coût unitaire en {{ $priceEntryCurrency }}" /><flux:input.group.suffix>{{ \App\Support\DisplayCurrency::symbol($priceEntryCurrency) }}</flux:input.group.suffix></flux:input.group>
                                <flux:error name="cost" />
                                <flux:text size="sm" class="mt-1">Aperçu secondaire : {{ $this->priceEntryEquivalent($cost) }}</flux:text>
                            </flux:field>
                            @endif
                            <flux:field>
                                <flux:label>Taxe de vente</flux:label>
                                <flux:input.group><flux:input type="number" step="0.01" min="0" max="100" wire:model="salesTaxRate" /><flux:input.group.suffix>%</flux:input.group.suffix></flux:input.group>
                                <flux:error name="salesTaxRate" />
                            </flux:field>
                        </div>
                        <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $taxIncluded ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}"><flux:icon.receipt-percent class="size-5" /></span>
                                <div><flux:heading size="sm">{{ $taxIncluded ? 'Prix saisi toutes taxes comprises (TTC)' : 'Prix saisi hors taxe (HT)' }}</flux:heading><flux:text size="sm" class="mt-1">{{ $taxIncluded ? 'Le POS extrait la TVA du prix sans augmenter le total client.' : 'Le POS ajoute la TVA au prix lors de la vente.' }}</flux:text></div>
                            </div>
                            <flux:switch wire:model.live="taxIncluded" label="Taxe incluse" align="left" />
                        </div>
                        <flux:error name="taxIncluded" />
                    </flux:fieldset>
                </flux:card>

                @if($type === 'product')
                    <flux:card class="p-5!">
                        <flux:fieldset legend="Gestion du stock" description="Quantités disponibles et seuil d’alerte.">
                            @if(!$productId)<div class="grid gap-4 sm:grid-cols-2"><flux:input label="Stock initial" type="number" step="0.001" min="0" wire:model="stockQty" /><flux:input label="Seuil d’alerte" type="number" step="0.001" min="0" wire:model="alertQty" /></div>@else<flux:input label="Seuil d’alerte" type="number" step="0.001" min="0" wire:model="alertQty" />@endif
                        </flux:fieldset>
                    </flux:card>
                @endif

                <flux:card class="p-5!">
                    <flux:textarea label="Notes internes" description="Ces informations restent réservées à l’équipe et ne figurent pas sur les tickets." rows="4" wire:model="internalNotes" />
                </flux:card>

                @if($productId && $type === 'product')<flux:callout icon="information-circle" text="Le stock existant se corrige depuis l’onglet Opérations afin de préserver son historique." />@endif
                <div class="sticky bottom-0 -mx-8 flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-8 pb-1 pt-5 dark:border-slate-700 dark:bg-zinc-800 sm:flex-row sm:justify-end"><flux:button type="button" variant="ghost" wire:click="$set('showProductModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="blue" icon="check">{{ $productId ? 'Enregistrer les modifications' : 'Créer l’article' }}</flux:button></div>
            </form>
        </flux:modal>
    @endif

    @if($showCategoryModal)<flux:modal wire:model="showCategoryModal" variant="flyout" position="left" class="w-full md:w-[32rem]"><div class="flex items-start gap-4 border-b border-slate-200 pb-5 pr-10 dark:border-slate-700"><span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-500/10"><flux:icon.tag class="size-5" /></span><div><flux:heading size="lg">{{ $categoryId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</flux:heading><flux:text size="sm" class="mt-1">Organisez clairement les produits du catalogue.</flux:text></div></div><form wire:submit="saveCategory" class="mt-6 space-y-4"><flux:input label="Nom" wire:model="categoryFormName" autofocus /><flux:input label="Code SKU" description="Lettres, chiffres, tirets et soulignement" wire:model="categoryCode" /><flux:switch wire:model="categoryIsActive" label="Catégorie active" /><div class="flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-slate-700"><flux:button type="button" variant="ghost" wire:click="$set('showCategoryModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="purple" icon="check">Enregistrer</flux:button></div></form></flux:modal>@endif

    @if($showUnitModal)<flux:modal wire:model="showUnitModal" variant="flyout" position="left" class="w-full md:w-[32rem]"><div class="flex items-start gap-4 border-b border-slate-200 pb-5 pr-10 dark:border-slate-700"><span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10"><flux:icon.scale class="size-5" /></span><div><flux:heading size="lg">{{ $unitId ? 'Modifier l’unité' : 'Nouvelle unité' }}</flux:heading><flux:text size="sm" class="mt-1">Définissez le format utilisé pour compter le stock.</flux:text></div></div><form wire:submit="saveUnit" class="mt-6 space-y-4"><div class="grid gap-4 sm:grid-cols-2"><flux:input label="Nom" wire:model="unitName" autofocus /><flux:input label="Symbole" wire:model="unitSymbol" placeholder="kg" /></div><flux:select label="Famille" wire:model="unitType">@foreach($unitTypeLabels as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach</flux:select><flux:switch wire:model="unitIsActive" label="Unité active" /><div class="flex justify-end gap-2 border-t border-slate-200 pt-5 dark:border-slate-700"><flux:button type="button" variant="ghost" wire:click="$set('showUnitModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" color="cyan" icon="check">Enregistrer</flux:button></div></form></flux:modal>@endif

    @if($showOperationModal)
        <flux:modal wire:model="showOperationModal" variant="flyout" position="right" class="w-full md:w-[38rem]">
            <div class="flex items-start gap-4"><span class="flex size-11 items-center justify-center rounded-xl {{ $operationMode === 'supply' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10' }}">@if($operationMode === 'supply')<flux:icon.truck class="size-5" />@else<flux:icon.arrows-right-left class="size-5" />@endif</span><div><flux:heading size="lg">{{ $operationMode === 'supply' ? 'Nouvel approvisionnement' : 'Ajustement du stock' }}</flux:heading><flux:text class="mt-1">{{ $operationMode === 'supply' ? 'Réception fournisseur avec valorisation comptable.' : 'Correction manuelle entièrement journalisée.' }}</flux:text></div></div>
            <form wire:submit="saveOperation" class="mt-6 space-y-4"><flux:select label="Produit" wire:model.live="selectedProductId"><flux:select.option value="">Sélectionner…</flux:select.option>@foreach($stockProducts as $product)<flux:select.option value="{{ $product->id }}">{{ $product->name }} · {{ number_format($product->stock_qty, 3, ',', ' ') }} {{ $product->unitOfMeasure?->symbol }}</flux:select.option>@endforeach</flux:select>@if($operationMode === 'adjustment')<flux:select label="Sens du mouvement" wire:model="adjustType"><flux:select.option value="in">Entrée / ajout</flux:select.option><flux:select.option value="out">Sortie / retrait</flux:select.option></flux:select>@endif<div class="grid gap-4 sm:grid-cols-2"><flux:input label="Quantité" type="number" step="0.001" min="0.001" wire:model="adjustQty" /><flux:input label="Référence" wire:model="operationReference" /></div>@if($operationMode === 'supply')<flux:input label="Coût unitaire ({{ \App\Support\DisplayCurrency::baseCode() }})" inputmode="decimal" autocomplete="off" placeholder="0,00" wire:model="operationUnitCost" />@endif<flux:textarea label="Motif / observation" rows="3" wire:model="adjustDesc" /><div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><flux:button type="button" variant="ghost" wire:click="$set('showOperationModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="check">Valider l’opération</flux:button></div></form>
        </flux:modal>
    @endif

    @if($showPhysicalInventoryModal)
        <flux:modal wire:model="showPhysicalInventoryModal" variant="flyout" position="right" class="w-full md:w-[52rem]">
            <div class="flex items-start gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-500/10"><flux:icon.clipboard-document-check class="size-5" /></span><div><flux:heading size="lg">Comptage physique</flux:heading><flux:text class="mt-1">Renseignez la quantité observée pour chaque article.</flux:text></div></div>
            <form wire:submit="applyPhysicalInventory" class="mt-6"><div class="yb-scrollbar max-h-[60vh] space-y-2 overflow-y-auto pr-1">@foreach($stockProducts as $product)<div class="grid items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800 sm:grid-cols-[1fr_9rem_10rem]"><div class="min-w-0"><p class="truncate font-bold">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->sku }}</p></div><div class="text-sm"><span class="block text-xs text-slate-500">Théorique</span><strong>{{ number_format($product->stock_qty, 3, ',', ' ') }} {{ $product->unitOfMeasure?->symbol }}</strong></div><flux:input aria-label="Quantité comptée pour {{ $product->name }}" type="number" step="0.001" min="0" wire:model="inventoryCounts.{{ $product->id }}" /></div>@endforeach</div><div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 dark:border-slate-800 sm:flex-row sm:justify-end"><flux:button type="button" variant="ghost" wire:click="$set('showPhysicalInventoryModal', false)">Annuler</flux:button><flux:button type="submit" variant="primary" icon="check">Valider l’inventaire</flux:button></div></form>
        </flux:modal>
    @endif

    @if($showDeleteModal)<flux:modal wire:model="showDeleteModal" class="md:w-[30rem]"><div class="flex size-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10"><flux:icon.trash class="size-5" /></div><flux:heading size="lg" class="mt-4">Supprimer cet article ?</flux:heading><flux:text class="mt-2">Un article déjà utilisé sera désactivé afin de conserver l’historique des ventes.</flux:text><div class="mt-6 flex justify-end gap-2"><flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Annuler</flux:button><flux:button variant="danger" wire:click="deleteProduct">Supprimer</flux:button></div></flux:modal>@endif
</div>
