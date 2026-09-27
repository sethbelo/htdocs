<div>
    <x-ui.page-header title="Chantiers" eyebrow="Opérations" subtitle="Personnel, matériel, présences, bons, rapports, paie et évaluation de chaque chantier.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="clipboard-document-check" :href="route('attendance')">Appel du jour</x-ui.button>
            @if (auth()->user()->canManage('sites'))<x-ui.button variant="accent" icon="plus" wire:click="create">Nouveau chantier</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-1 overflow-x-auto scrollbar-none">
            <button type="button" wire:click="$set('status', '')" @class(['btn btn-sm', 'btn-soft' => $status === '', 'btn-ghost' => $status !== ''])>Tous <span class="text-fg-subtle">{{ $counts->sum() }}</span></button>
            @foreach (config('erp.statuses.site') as $key => [$label])
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['btn btn-sm', 'btn-soft' => $status === $key, 'btn-ghost' => $status !== $key])>{{ $label }} <span class="text-fg-subtle">{{ $counts[$key] ?? 0 }}</span></button>
            @endforeach
        </div>
        <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom, code, commune…" />
    </div>

    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @forelse ($sites as $site)
            @php
                $cost = $costs[$site->id] ?? 0;
                $ratio = (float) $site->budget > 0 ? $cost / (float) $site->budget * 100 : 0;
            @endphp
            <a href="{{ route('sites.show', $site) }}" wire:navigate class="card group flex flex-col p-5 transition hover:border-brand-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[12px] font-semibold text-fg-subtle">{{ $site->code }}</p>
                        <h3 class="mt-0.5 line-clamp-2 font-display text-[17px] leading-snug font-semibold group-hover:text-brand-600">{{ $site->name }}</h3>
                        <p class="mt-1 flex items-center gap-1 text-[12.5px] text-fg-muted"><x-ui.icon name="map-pin" class="size-4" />{{ $site->city ?? '—' }} · {{ $site->contact?->display_name ?? 'Chantier interne' }}</p>
                    </div>
                    <x-ui.status map="site" :value="$site->status" />
                </div>
                <div class="mt-4 flex items-center gap-4">
                    <div class="relative grid size-16 shrink-0 place-items-center">
                        <svg viewBox="0 0 36 36" class="absolute inset-0 -rotate-90"><circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--ui-line)" stroke-width="3.5"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $site->isDelayed() ? '#F7313A' : '#FFCE31' }}" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="{{ round($site->progress * 0.974, 1) }} 100"/></svg>
                        <span class="font-display text-[15px] font-semibold">{{ $site->progress }}%</span>
                    </div>
                    <dl class="grid flex-1 grid-cols-2 gap-x-3 gap-y-1.5 text-[12.5px]">
                        <div><dt class="text-fg-subtle">Responsable</dt><dd class="truncate font-medium">{{ $site->manager?->full_name ?? '—' }}</dd></div>
                        <div><dt class="text-fg-subtle">Équipe</dt><dd class="font-medium">{{ $site->members_count }} pers.</dd></div>
                        <div><dt class="text-fg-subtle">Fin prévue</dt><dd @class(['font-medium', 'text-flame-600' => $site->isDelayed()])>{{ $site->planned_end_date?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-fg-subtle">Budget</dt><dd class="font-medium tabular-nums">{{ money($site->budget, 'USD', 0) }}</dd></div>
                    </dl>
                </div>
                <div class="mt-4 border-t border-line pt-3">
                    <div class="mb-1 flex justify-between text-[12px]"><span class="text-fg-subtle">Dépenses (main-d’œuvre + bons)</span><span class="font-semibold tabular-nums">{{ money($cost, 'USD', 0) }}</span></div>
                    <x-ui.progress :value="min(100, round($ratio))" :tone="$ratio > 90 ? 'flame' : 'lagoon'" />
                </div>
            </a>
        @empty
            <div class="card md:col-span-2 2xl:col-span-3"><x-ui.empty icon="home-modern" title="Aucun chantier" text="Les chantiers sont créés depuis une commande « construction » ou manuellement." /></div>
        @endforelse
    </div>

    <x-ui.drawer wire:model="showForm" title="Nouveau chantier" icon="home-modern" size="lg">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input label="Nom du chantier" wire:model="name" class="sm:col-span-2" required placeholder="Ex. : Construction entrepôt Kingabwa" />
            <x-ui.select label="Maître d’ouvrage (client)" wire:model="contactId" placeholder="Chantier interne" :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->display_name])" />
            <x-ui.select label="Responsable" wire:model="managerId" placeholder="À désigner" :options="$managers->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' — '.($e->position?->title ?? '')])" />
            <x-ui.input label="Adresse" wire:model="address" />
            <x-ui.input label="Commune / ville" wire:model="city" />
            <x-ui.input label="Début" type="date" wire:model="startDate" required />
            <x-ui.input label="Fin prévue" type="date" wire:model="plannedEndDate" required />
            <x-ui.input label="Budget" type="number" step="0.01" wire:model="budget" suffix="USD" />
            <div class="sm:col-span-2"><x-ui.textarea label="Description des travaux" wire:model="description" rows="3" /></div>
            <div class="sm:col-span-2"><x-ui.toggle wire:model="createWarehouse" label="Créer un magasin de chantier" description="Permet de transférer les matériaux vers le site et d’y suivre le stock." /></div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Créer le chantier</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
