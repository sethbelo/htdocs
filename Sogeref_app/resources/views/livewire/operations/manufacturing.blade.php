<div>
    {{-- ═══ 1. EN-TÊTE ═══════════════════════════════════════════════════════ --}}
    <x-ui.page-header title="Atelier & fabrication" eyebrow="Opérations" subtitle="Ordres de fabrication (atelier) et dossiers de construction (chantiers). Glissez une carte pour la faire avancer.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="rectangle-group" :href="route('manufacturing.boms')">Nomenclatures</x-ui.button>
            @if (auth()->user()->canManage('manufacturing'))
                <x-ui.button variant="accent" icon="plus" wire:click="openCreate">Ordre pour le stock</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ═══ 2. INDICATEURS ═══════════════════════════════════════════════════ --}}
    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Planifiés" :value="$stats['planned']" icon="calendar-days" />
        <x-ui.stat label="En production" :value="$stats['progress']" icon="cog-6-tooth" tone="sun" />
        <x-ui.stat label="Contrôle qualité" :value="$stats['quality']" icon="shield-check" tone="lagoon" />
        <x-ui.stat label="En retard" :value="$stats['late']" icon="exclamation-triangle" :tone="$stats['late'] ? 'flame' : 'emerald'" />
    </div>

    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
        <x-ui.search wire:model.live.debounce.300ms="search" placeholder="N° ou intitulé…" />
        <div class="segmented self-start">
            <button type="button" wire:click="$set('type', '')" aria-pressed="{{ $type === '' ? 'true' : 'false' }}">Tous</button>
            <button type="button" wire:click="$set('type', 'fabrication')" aria-pressed="{{ $type === 'fabrication' ? 'true' : 'false' }}">Fabrication</button>
            <button type="button" wire:click="$set('type', 'construction')" aria-pressed="{{ $type === 'construction' ? 'true' : 'false' }}">Construction</button>
        </div>
    </div>

    {{-- ═══ 3. TABLEAU DE PRODUCTION ═════════════════════════════════════════ --}}
    <div class="kanban" x-data="kanban('move')">
        @foreach (\App\Livewire\Operations\Manufacturing::COLUMNS as $status)
            @php
                [$label, $tone] = erp_status('manufacturing', $status);
                $items = $columns->get($status, collect());
            @endphp
            <section class="kanban-col w-[300px]">
                <header class="flex items-center justify-between px-2 pt-1 pb-2.5">
                    <x-ui.badge :tone="$tone" dot>{{ $label }}</x-ui.badge>
                    <span class="text-[12px] font-semibold text-fg-subtle">{{ $items->count() }}</span>
                </header>
                <div class="flex min-h-40 flex-col gap-2" data-column="{{ $status }}">
                    @foreach ($items as $mo)
                        <article class="kanban-card" data-id="{{ $mo->id }}" wire:key="mo-{{ $mo->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('manufacturing.show', $mo) }}" wire:navigate class="text-[11.5px] font-semibold text-fg-subtle hover:text-brand-600">{{ $mo->number }}</a>
                                <span class="flex items-center gap-1">
                                    @if ($mo->isDelayed())<x-ui.badge tone="danger">Retard</x-ui.badge>@endif
                                    <x-ui.icon :name="$mo->type === 'construction' ? 'home-modern' : 'wrench-screwdriver'" class="size-4 text-fg-subtle" />
                                </span>
                            </div>
                            <a href="{{ route('manufacturing.show', $mo) }}" wire:navigate class="mt-1 block text-[13.5px] leading-snug font-semibold hover:text-brand-600">{{ $mo->title }}</a>
                            <p class="mt-1 truncate text-[12px] text-fg-muted">{{ $mo->salesOrder?->contact?->display_name ?? 'Production pour le stock' }}</p>
                            <div class="mt-2.5 flex items-center justify-between text-[12px] text-fg-subtle">
                                <span>{{ qty($mo->quantity) }} {{ $mo->product?->unit ?? 'ouvrage' }}</span>
                                <span class="flex items-center gap-1"><x-ui.icon name="calendar" class="size-3.5" />{{ $mo->planned_end?->format('d/m') }}</span>
                            </div>
                            <x-ui.progress :value="$mo->progress" class="mt-2" :tone="$mo->status === 'done' ? 'emerald' : 'sun'" />
                            @if ($mo->responsible)
                                <p class="mt-2 flex items-center gap-1.5 text-[12px] text-fg-muted"><x-ui.avatar :name="$mo->responsible->full_name" size="xs" />{{ $mo->responsible->full_name }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    {{-- ═══ 4. CRÉATION D'UN ORDRE POUR LE STOCK ═════════════════════════════ --}}
    <x-ui.drawer wire:model="showCreate" title="Ordre de fabrication pour le stock" icon="wrench-screwdriver">
        <div class="space-y-4">
            <p class="rounded-xl bg-brand-50 p-3.5 text-[13px] text-brand-700 dark:bg-white/6 dark:text-brand-100">La liste de matériel est calculée automatiquement depuis la nomenclature du produit.</p>
            <x-ui.select label="Produit à fabriquer" wire:model="productId" placeholder="Choisir…" :options="$products->mapWithKeys(fn ($p) => [$p->id => $p->sku.' · '.$p->name])" required />
            <x-ui.input label="Quantité" type="number" step="1" min="1" wire:model="quantity" required />
            <x-ui.select label="Responsable" wire:model="responsibleId" placeholder="À désigner" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name])" />
            <x-ui.input label="Fin prévue" type="date" wire:model="plannedEnd" required />
            <x-ui.select label="Priorité" wire:model="priority" :options="collect(config('erp.statuses.priority'))->map(fn ($p) => $p[0])" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="create">Créer l’ordre</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
