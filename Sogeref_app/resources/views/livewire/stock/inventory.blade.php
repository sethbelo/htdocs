<div>
    @php $canManage = auth()->user()->canManage('inventory'); @endphp
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header title="Stock & entrepôts" eyebrow="Stock & achats" subtitle="Articles, niveaux par entrepôt, mouvements tracés, parc matériel et inventaires physiques.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="arrows-right-left" wire:click="newMove('transfer')">Transfert</x-ui.button>
                <x-ui.button variant="secondary" icon="arrow-down-tray" wire:click="newMove('in')">Entrée</x-ui.button>
                <x-ui.button variant="accent" icon="plus" wire:click="editProduct">Nouvel article</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Valeur du stock" :value="money($stats['value'], 'USD', 0)" icon="banknotes" hint="au dernier coût d’achat" />
        <x-ui.stat label="Articles suivis" :value="$stats['items']" icon="cube" tone="lagoon" />
        <x-ui.stat label="Sous le seuil" :value="$stats['low']" icon="exclamation-triangle" :tone="$stats['low'] ? 'flame' : 'emerald'" :href="route('purchases')" />
        <x-ui.stat label="Parc matériel" :value="$stats['equipment']" icon="wrench" tone="sun" />
    </div>

    <div class="card">
        <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['articles' => ['label' => 'Articles', 'icon' => 'cube'], 'niveaux' => ['label' => 'Niveaux par entrepôt', 'icon' => 'table-cells'], 'mouvements' => ['label' => 'Mouvements', 'icon' => 'arrows-right-left'], 'entrepots' => ['label' => 'Entrepôts', 'icon' => 'building-storefront'], 'materiel' => ['label' => 'Parc matériel', 'icon' => 'wrench'], 'inventaires' => ['label' => 'Inventaires', 'icon' => 'clipboard-document-list']]" /></div>

        @if (in_array($tab, ['articles', 'niveaux', 'mouvements', 'materiel']))
            <div class="flex flex-col gap-2 border-b border-line p-3 sm:flex-row sm:items-center">
                <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Code ou désignation…" />
                @if ($tab === 'articles')
                    <select wire:model.live="productType" class="input sm:w-52"><option value="">Tous les types</option>@foreach (config('erp.product_types') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <label class="flex items-center gap-2 text-[13px] text-fg-muted"><input type="checkbox" wire:model.live="lowOnly" class="checkbox"> Sous le seuil uniquement</label>
                @endif
                @if ($tab === 'materiel' && $canManage)<x-ui.button variant="soft" size="sm" icon="plus" wire:click="editEquipment" class="sm:ml-auto">Ajouter un équipement</x-ui.button>@endif
            </div>
        @endif

        {{-- ─── ARTICLES ───────────────────────────────────────────────────── --}}
        @if ($tab === 'articles')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Article</th><th>Type</th><th>Catégorie</th><th class="num">Stock</th><th class="num">Seuil</th><th class="num">Coût</th><th class="num">Prix de vente</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($products as $p)
                            <tr wire:key="p-{{ $p->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($p->imageUrl())<img src="{{ $p->imageUrl() }}" class="size-10 rounded-lg object-cover" alt="">@else<span class="grid size-10 place-items-center rounded-lg bg-surface-2 text-fg-subtle"><x-ui.icon name="cube" class="size-5" /></span>@endif
                                        <div class="min-w-0"><p class="truncate font-semibold">{{ $p->name }}</p><p class="text-[12px] text-fg-subtle">{{ $p->sku }}@unless ($p->is_active) · <span class="text-flame-600">archivé</span>@endunless</p></div>
                                    </div>
                                </td>
                                <td><x-ui.badge :tone="['finished' => 'brand', 'material' => 'warning', 'consumable' => 'neutral', 'product' => 'info', 'service' => 'accent'][$p->type] ?? 'neutral'">{{ $p->typeLabel() }}</x-ui.badge></td>
                                <td class="text-fg-muted">{{ $p->category?->name }}</td>
                                <td class="num"><span @class(['font-semibold', 'text-flame-600' => $p->isLow()])>{{ $p->track_stock ? qty($p->stock_quantity) : '—' }}</span> <span class="text-[11px] text-fg-subtle">{{ $p->unit }}</span></td>
                                <td class="num text-fg-muted">{{ (float) $p->reorder_level ? qty($p->reorder_level) : '—' }}</td>
                                <td class="num">{{ money($p->purchase_price) }}</td>
                                <td class="num font-semibold">{{ (float) $p->sale_price ? money($p->sale_price) : '—' }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($canManage)
                                        @if ($p->track_stock)<x-ui.button variant="ghost" size="sm" square icon="arrows-right-left" wire:click="newMove('in', {{ $p->id }})" title="Mouvement" />@endif
                                        <x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editProduct({{ $p->id }})" title="Modifier" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="cube" title="Aucun article" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="border-t border-line px-4 py-3">{{ $products->links('components.ui.pagination') }}</div>@endif

        {{-- ─── NIVEAUX ────────────────────────────────────────────────────── --}}
        @elseif ($tab === 'niveaux')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th class="sticky left-0 bg-surface-2">Article</th>@foreach ($warehouses as $w)<th class="num">{{ $w->code }}</th>@endforeach<th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach ($matrix as $p)
                            @php $levels = $p->levels->keyBy('warehouse_id'); @endphp
                            <tr>
                                <td class="sticky left-0 bg-surface"><p class="font-semibold">{{ $p->name }}</p><p class="text-[12px] text-fg-subtle">{{ $p->sku }} · {{ $p->unit }}</p></td>
                                @foreach ($warehouses as $w)
                                    @php $q = (float) ($levels[$w->id]->quantity ?? 0); @endphp
                                    <td class="num {{ $q == 0 ? 'text-fg-subtle' : ($q < 0 ? 'font-semibold text-flame-600' : '') }}">{{ $q == 0 ? '·' : qty($q) }}</td>
                                @endforeach
                                <td @class(['num font-semibold', 'text-flame-600' => $p->isLow()])>{{ qty($p->stock_quantity) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 border-t border-line px-4 py-3 text-[12px] text-fg-subtle">@foreach ($warehouses as $w)<span><strong class="text-fg-muted">{{ $w->code }}</strong> {{ $w->name }}</span>@endforeach</div>
            @if ($matrix->hasPages())<div class="border-t border-line px-4 py-3">{{ $matrix->links('components.ui.pagination') }}</div>@endif

        {{-- ─── MOUVEMENTS ─────────────────────────────────────────────────── --}}
        @elseif ($tab === 'mouvements')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th>Type</th><th>Article</th><th>De</th><th>Vers</th><th class="num">Quantité</th><th>Référence</th><th>Par</th></tr></thead>
                    <tbody>
                        @forelse ($moves as $m)
                            @php [$label, $tone] = \App\Models\StockMove::TYPES[$m->type] ?? [$m->type, 'neutral']; @endphp
                            <tr>
                                <td class="whitespace-nowrap text-fg-muted">{{ $m->moved_at->format('d/m/Y H:i') }}</td>
                                <td><x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge></td>
                                <td class="max-w-[240px]"><p class="truncate font-medium">{{ $m->product?->name }}</p><p class="truncate text-[12px] text-fg-subtle">{{ $m->notes }}</p></td>
                                <td class="text-fg-muted">{{ $m->from?->code ?? '—' }}</td>
                                <td class="text-fg-muted">{{ $m->to?->code ?? '—' }}</td>
                                <td class="num font-semibold {{ $m->to_warehouse_id && ! $m->from_warehouse_id ? 'text-emerald-600' : ($m->from_warehouse_id && ! $m->to_warehouse_id ? 'text-flame-600' : '') }}">{{ $m->to_warehouse_id && ! $m->from_warehouse_id ? '+' : ($m->from_warehouse_id && ! $m->to_warehouse_id ? '−' : '') }}{{ qty($m->quantity) }}</td>
                                <td class="text-[12.5px]">{{ $m->reference }}</td>
                                <td class="text-fg-muted">{{ $m->user?->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="arrows-right-left" title="Aucun mouvement" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($moves->hasPages())<div class="border-t border-line px-4 py-3">{{ $moves->links('components.ui.pagination') }}</div>@endif

        {{-- ─── ENTREPÔTS ──────────────────────────────────────────────────── --}}
        @elseif ($tab === 'entrepots')
            <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($warehouses as $w)
                    <div class="rounded-xl border border-line p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-white/8 dark:text-lagoon-300"><x-ui.icon :name="['central' => 'building-office-2', 'atelier' => 'wrench-screwdriver', 'chantier' => 'home-modern', 'showroom' => 'building-storefront'][$w->type] ?? 'cube'" /></span>
                                <div><p class="font-semibold">{{ $w->name }}</p><p class="text-[12px] text-fg-subtle">{{ $w->code }} · {{ \App\Models\Warehouse::TYPES[$w->type] }}</p></div>
                            </div>
                            @if ($canManage)<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editWarehouse({{ $w->id }})" />@endif
                        </div>
                        <p class="mt-3 text-[12.5px] text-fg-muted">{{ $w->address ?? '—' }}@if ($w->site) · chantier {{ $w->site->code }}@endif</p>
                        <p class="mt-2 text-[12.5px]"><span class="text-fg-subtle">Valeur :</span> <strong>{{ money($w->levels()->join('products', 'products.id', '=', 'stock_levels.product_id')->sum(\Illuminate\Support\Facades\DB::raw('stock_levels.quantity * products.purchase_price')), 'USD', 0) }}</strong> @unless ($w->is_active)<x-ui.badge tone="neutral">Inactif</x-ui.badge>@endunless</p>
                    </div>
                @endforeach
                @if ($canManage)
                    <button type="button" wire:click="editWarehouse" class="grid min-h-32 place-items-center rounded-xl border-2 border-dashed border-line-strong text-[13.5px] font-semibold text-fg-subtle transition hover:border-brand-300 hover:text-brand-600"><span class="flex items-center gap-2"><x-ui.icon name="plus" /> Ajouter un entrepôt</span></button>
                @endif
            </div>

        {{-- ─── MATÉRIEL ───────────────────────────────────────────────────── --}}
        @elseif ($tab === 'materiel')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Équipement</th><th>Catégorie</th><th>Localisation</th><th>Statut</th><th class="num">Valeur</th><th class="num">Location / jour</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($equipmentList as $e)
                            <tr>
                                <td><p class="font-semibold">{{ $e->name }}</p><p class="text-[12px] text-fg-subtle">{{ $e->code }} · {{ $e->serial_number }}</p></td>
                                <td class="text-fg-muted">{{ $e->category }}</td>
                                <td class="text-fg-muted">{{ $e->site ? 'Chantier '.$e->site->code : ($e->warehouse?->name ?? '—') }}</td>
                                <td><x-ui.status map="equipment" :value="$e->status" /></td>
                                <td class="num">{{ money($e->purchase_value, 'USD', 0) }}</td>
                                <td class="num">{{ $e->is_rentable ? money($e->daily_rent_price) : '—' }}</td>
                                <td class="text-right">@if ($canManage)<x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editEquipment({{ $e->id }})" />@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-ui.empty icon="wrench" title="Aucun équipement" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($equipmentList->hasPages())<div class="border-t border-line px-4 py-3">{{ $equipmentList->links('components.ui.pagination') }}</div>@endif

        {{-- ─── INVENTAIRES ────────────────────────────────────────────────── --}}
        @else
            <div class="p-4">
                @if ($currentCount)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div><p class="font-display text-[17px] font-semibold">{{ $currentCount->reference }} · {{ $currentCount->warehouse->name }}</p><p class="text-[12.5px] text-fg-subtle">Saisissez les quantités réellement comptées. Les lignes laissées vides ne sont pas ajustées.</p></div>
                        <div class="flex gap-2">
                            <x-ui.button variant="ghost" wire:click="$set('countId', null)">Fermer</x-ui.button>
                            <x-ui.button variant="secondary" icon="document" wire:click="saveCount">Enregistrer</x-ui.button>
                            <x-ui.button variant="primary" icon="check" wire:click="validateCount" wire:confirm="Valider l’inventaire ? Les écarts seront passés en ajustement de stock.">Valider et ajuster</x-ui.button>
                        </div>
                    </div>
                    <div class="table-wrap rounded-xl border border-line">
                        <table class="table">
                            <thead><tr><th>Article</th><th class="num">Théorique</th><th class="num w-36">Compté</th><th class="num">Écart</th></tr></thead>
                            <tbody>
                                @foreach ($currentCount->lines as $line)
                                    @php $gap = ($counted[$line->id] ?? '') === '' ? null : (float) $counted[$line->id] - (float) $line->expected; @endphp
                                    <tr>
                                        <td><p class="font-medium">{{ $line->product->name }}</p><p class="text-[12px] text-fg-subtle">{{ $line->product->sku }} · {{ $line->product->unit }}</p></td>
                                        <td class="num">{{ qty($line->expected) }}</td>
                                        <td><input type="number" step="0.01" wire:model.live.debounce.500ms="counted.{{ $line->id }}" class="input h-10 text-right"></td>
                                        <td @class(['num font-semibold', 'text-emerald-600' => $gap > 0, 'text-flame-600' => $gap < 0, 'text-fg-subtle' => $gap === null || $gap == 0])>{{ $gap === null ? '—' : ($gap > 0 ? '+' : '').qty($gap) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    @if ($canManage)
                        <div class="mb-4 flex flex-col gap-2 rounded-xl bg-surface-2 p-3 sm:flex-row">
                            <select wire:model="countWarehouseId" class="input sm:w-80"><option value="">Entrepôt à inventorier…</option>@foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
                            <x-ui.button variant="primary" icon="clipboard-document-list" wire:click="startCount">Démarrer un inventaire</x-ui.button>
                        </div>
                    @endif
                    <table class="table">
                        <thead><tr><th>Référence</th><th>Entrepôt</th><th>Date</th><th class="num">Lignes</th><th>Statut</th><th>Par</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($counts as $c)
                                <tr>
                                    <td class="font-semibold">{{ $c->reference }}</td><td>{{ $c->warehouse->name }}</td><td class="text-fg-muted">{{ $c->count_date->format('d/m/Y') }}</td>
                                    <td class="num">{{ $c->lines_count }}</td><td>@if ($c->status === 'validated')<x-ui.badge tone="success" dot>Validé</x-ui.badge>@else<x-ui.badge tone="warning" dot>En cours</x-ui.badge>@endif</td>
                                    <td class="text-fg-muted">{{ $c->user?->name }}</td>
                                    <td class="text-right">@if ($c->status !== 'validated' && $canManage)<x-ui.button variant="ghost" size="sm" icon="pencil-square" wire:click="openCount({{ $c->id }})">Reprendre</x-ui.button>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-ui.empty icon="clipboard-document-list" title="Aucun inventaire" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══ FENÊTRES ═════════════════════════════════════════════════════════ --}}
    <x-ui.drawer wire:model="showProduct" :title="$productId ? 'Modifier l’article' : 'Nouvel article'" icon="cube" size="lg">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Code (SKU)" wire:model="product.sku" required />
            <x-ui.select label="Type" wire:model.live="product.type" :options="config('erp.product_types')" />
            <x-ui.input label="Désignation" wire:model="product.name" class="sm:col-span-2" required />
            <x-ui.select label="Catégorie" wire:model="product.category_id" placeholder="—" :options="$categories->pluck('name', 'id')" />
            <x-ui.input label="Unité" wire:model="product.unit" list="product-units" />
            <div class="sm:col-span-2"><x-ui.textarea label="Description (reprise sur les devis)" wire:model="product.description" rows="2" /></div>
            <x-ui.input label="Prix de vente HT" type="number" step="0.01" wire:model="product.sale_price" suffix="USD" />
            <x-ui.input label="Coût d’achat" type="number" step="0.01" wire:model="product.purchase_price" suffix="USD" />
            <x-ui.input label="TVA" type="number" step="0.5" wire:model="product.tax_rate" suffix="%" />
            <x-ui.input label="Code-barres" wire:model="product.barcode" />
            <div class="rounded-xl border border-line p-4 sm:col-span-2">
                <x-ui.toggle wire:model.live="product.track_stock" label="Suivre le stock" description="Mouvements, niveaux par entrepôt et alertes de réapprovisionnement." />
                @if ($product['track_stock'] ?? false)
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <x-ui.input label="Seuil de réappro" type="number" step="0.01" wire:model="product.reorder_level" />
                        <x-ui.input label="Qté à commander" type="number" step="0.01" wire:model="product.reorder_quantity" />
                        <x-ui.input label="Délai (jours)" type="number" wire:model="product.lead_time_days" />
                        <x-ui.select label="Fournisseur habituel" wire:model="product.default_supplier_id" placeholder="—" :options="$suppliers->pluck('company_name', 'id')" class="sm:col-span-3" />
                    </div>
                @endif
            </div>
            <div class="grid gap-3 sm:col-span-2 sm:grid-cols-2">
                <x-ui.toggle wire:model="product.is_sellable" label="Vendable" />
                <x-ui.toggle wire:model="product.is_purchasable" label="Achetable" />
                <x-ui.toggle wire:model="product.pos_visible" label="Visible au point de vente" />
                <x-ui.toggle wire:model="product.is_active" label="Actif" />
            </div>
            <div class="sm:col-span-2">
                <label class="label">Photo</label>
                <input type="file" wire:model="image" accept="image/*" class="block w-full text-[13px] file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                @error('image')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <datalist id="product-units">@foreach (config('erp.units') as $u)<option value="{{ $u }}">@endforeach</datalist>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveProduct">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showMove" title="Mouvement de stock" icon="arrows-right-left">
        <div class="space-y-4">
            <div class="segmented">
                @foreach (['in' => 'Entrée', 'out' => 'Sortie', 'transfer' => 'Transfert', 'adjust' => 'Ajustement'] as $k => $l)
                    <button type="button" wire:click="$set('moveType', '{{ $k }}')" aria-pressed="{{ $moveType === $k ? 'true' : 'false' }}">{{ $l }}</button>
                @endforeach
            </div>
            <x-ui.select label="Article" wire:model="moveProductId" placeholder="Choisir…" :options="$catalog->mapWithKeys(fn ($p) => [$p->id => $p->sku.' · '.$p->name.' ('.$p->unit.')'])" required />
            @if (in_array($moveType, ['out', 'transfer']))<x-ui.select label="Depuis" wire:model="fromWarehouseId" :options="$warehouses->pluck('name', 'id')" placeholder="—" />@endif
            @if (in_array($moveType, ['in', 'transfer', 'adjust']))<x-ui.select :label="$moveType === 'adjust' ? 'Entrepôt' : 'Vers'" wire:model="toWarehouseId" :options="$warehouses->pluck('name', 'id')" placeholder="—" />@endif
            <x-ui.input :label="$moveType === 'adjust' ? 'Quantité réelle constatée' : 'Quantité'" type="number" step="0.01" wire:model="moveQuantity" />
            @if ($moveType === 'in')<x-ui.input label="Coût unitaire" type="number" step="0.01" wire:model="moveCost" suffix="USD" />@endif
            <x-ui.input label="Motif" wire:model="moveNotes" placeholder="Ex. : casse, don, retour client…" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveMove">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showWarehouse" :title="$warehouseId ? 'Modifier l’entrepôt' : 'Nouvel entrepôt'" icon="building-storefront">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Code" wire:model="warehouse.code" required /><x-ui.select label="Type" wire:model="warehouse.type" :options="\App\Models\Warehouse::TYPES" /></div>
            <x-ui.input label="Nom" wire:model="warehouse.name" required />
            <x-ui.select label="Chantier rattaché" wire:model="warehouse.site_id" placeholder="Aucun" :options="$sites->mapWithKeys(fn ($s) => [$s->id => $s->code.' · '.$s->name])" />
            <x-ui.input label="Adresse" wire:model="warehouse.address" />
            <x-ui.toggle wire:model="warehouse.is_active" label="Actif" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveWarehouse">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showEquipment" :title="$equipmentId ? 'Modifier l’équipement' : 'Nouvel équipement'" icon="wrench">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Code" wire:model="equipment.code" required />
            <x-ui.input label="N° de série" wire:model="equipment.serial_number" />
            <x-ui.input label="Désignation" wire:model="equipment.name" class="sm:col-span-2" required />
            <x-ui.input label="Catégorie" wire:model="equipment.category" placeholder="Engins, échafaudage, atelier…" />
            <x-ui.select label="Statut" wire:model="equipment.status" :options="collect(config('erp.statuses.equipment'))->map(fn ($s) => $s[0])" />
            <x-ui.input label="Valeur d’achat" type="number" step="0.01" wire:model="equipment.purchase_value" suffix="USD" />
            <x-ui.select label="Dépôt de rattachement" wire:model="equipment.warehouse_id" placeholder="—" :options="$warehouses->pluck('name', 'id')" />
            <div class="sm:col-span-2 rounded-xl border border-line p-4">
                <x-ui.toggle wire:model.live="equipment.is_rentable" label="Disponible à la location" />
                @if ($equipment['is_rentable'] ?? false)<x-ui.input label="Tarif journalier" type="number" step="0.01" wire:model="equipment.daily_rent_price" suffix="USD/j" class="mt-3" />@endif
            </div>
            <div class="sm:col-span-2"><x-ui.textarea label="Notes" wire:model="equipment.notes" rows="2" /></div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveEquipment">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
