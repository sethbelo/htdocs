<div>
    @php
        $canManage = auth()->user()->canManage('appointments');
        $typeColors = ['reunion' => 'bg-brand-600', 'visite_chantier' => 'bg-sun-400', 'showroom' => 'bg-lagoon-400', 'mesure' => 'bg-violet-500', 'livraison' => 'bg-emerald-500'];
    @endphp
    <x-ui.page-header title="Rendez-vous" eyebrow="Réception" subtitle="Agenda partagé : visites showroom, visites de chantier, prises de mesures, livraisons et réunions.">
        <x-slot:actions>
            <label class="flex items-center gap-2 text-[13px] text-fg-muted"><input type="checkbox" wire:model.live="mine" class="checkbox"> Mon agenda</label>
            <x-ui.button variant="secondary" square icon="chevron-left" wire:click="shift(-1)" />
            <x-ui.button variant="secondary" wire:click="$set('week', '{{ now()->startOfWeek()->toDateString() }}')">Aujourd’hui</x-ui.button>
            <x-ui.button variant="secondary" square icon="chevron-right" wire:click="shift(1)" />
            @if ($canManage)<x-ui.button variant="accent" icon="plus" wire:click="edit">Rendez-vous</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_300px]">
        {{-- ═══ SEMAINE ══════════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <div class="grid min-w-[860px] grid-cols-[56px_repeat(6,1fr)]">
                    <div class="border-b border-line bg-surface-2"></div>
                    @foreach ($days as $d)
                        <div @class(['border-b border-l border-line bg-surface-2 px-2 py-2.5 text-center', 'text-sun-700 dark:text-sun-300' => $d->isToday()])><p class="text-[11px] font-semibold tracking-wider uppercase">{{ $d->translatedFormat('D') }}</p><p class="font-display text-[18px] font-semibold">{{ $d->format('d') }}</p></div>
                    @endforeach
                    @foreach ($hours as $h)
                        <div class="h-16 border-b border-line pt-1 pr-2 text-right text-[11px] text-fg-subtle">{{ sprintf('%02d:00', $h) }}</div>
                        @foreach ($days as $d)
                            <div class="relative h-16 border-b border-l border-line/70" @if ($canManage) wire:click.self="edit(null, '{{ $d->toDateString() }}', '{{ sprintf('%02d:00', $h) }}')" @endif>
                                @foreach (($byDay[$d->toDateString()] ?? collect())->filter(fn ($a) => (int) $a->starts_at->format('G') === $h) as $a)
                                    @php
                                        $top = (int) $a->starts_at->format('i') / 60 * 64;
                                        $height = max(26, $a->ends_at->diffInMinutes($a->starts_at, true) / 60 * 64 - 3);
                                    @endphp
                                    <button type="button" wire:click="edit({{ $a->id }})" class="absolute inset-x-1 z-10 overflow-hidden rounded-md border-l-[3px] bg-surface px-1.5 py-1 text-left shadow-sm ring-1 ring-line transition hover:ring-brand-300 {{ in_array($a->status, ['cancelled', 'no_show']) ? 'opacity-50' : '' }}"
                                            style="top: {{ $top }}px; height: {{ $height }}px; border-left-color: var(--color-{{ ['reunion' => 'brand-600', 'visite_chantier' => 'sun-400', 'showroom' => 'lagoon-400', 'mesure' => 'violet-500', 'livraison' => 'emerald-500'][$a->type] ?? 'brand-600' }})">
                                        <p class="truncate text-[11.5px] font-semibold">{{ $a->starts_at->format('H:i') }} · {{ $a->title }}</p>
                                        <p class="truncate text-[11px] text-fg-subtle">{{ $a->whoName() }} · {{ $a->user?->name }}</p>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <div class="flex flex-wrap gap-4 border-t border-line px-4 py-3 text-[12px] text-fg-muted">
                @foreach (config('erp.appointment_types') as $k => $l)<span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full {{ $typeColors[$k] }}"></span>{{ $l }}</span>@endforeach
            </div>
        </div>

        {{-- ═══ À VENIR ══════════════════════════════════════════════════════ --}}
        <x-ui.card title="Prochains rendez-vous" icon="calendar" :padded="false">
            <ul>
                @forelse ($upcoming as $a)
                    <li class="border-b border-line px-5 py-3 last:border-0">
                        <p class="text-[12px] font-semibold text-lagoon-700 dark:text-lagoon-300">{{ ucfirst($a->starts_at->translatedFormat('l j M · H:i')) }}</p>
                        <p class="font-semibold">{{ $a->title }}</p>
                        <p class="text-[12.5px] text-fg-muted">{{ $a->whoName() }} · {{ $a->location }}</p>
                    </li>
                @empty
                    <li><x-ui.empty icon="calendar" title="Rien de prévu" /></li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>

    <x-ui.drawer wire:model="showForm" :title="$editing ? 'Rendez-vous' : 'Nouveau rendez-vous'" icon="calendar">
        <div class="space-y-4">
            <x-ui.input label="Objet" wire:model="form.title" required />
            <x-ui.select label="Type" wire:model="form.type" :options="config('erp.appointment_types')" />
            <x-ui.select label="Client / prospect" wire:model="form.contact_id" placeholder="Visiteur non enregistré" :options="$contacts->mapWithKeys(fn ($c) => [$c->id => $c->display_name])" />
            @if (empty($form['contact_id']))<div class="grid grid-cols-2 gap-3"><x-ui.input label="Nom du visiteur" wire:model="form.visitor_name" /><x-ui.input label="Téléphone" wire:model="form.visitor_phone" /></div>@endif
            <x-ui.select label="Avec" wire:model="form.user_id" :options="$users->pluck('name', 'id')" />
            <div class="grid grid-cols-3 gap-3">
                <x-ui.input label="Date" type="date" wire:model="form.date" />
                <x-ui.input label="Heure" type="time" wire:model="form.start" />
                <x-ui.select label="Durée" wire:model="form.duration" :options="[30 => '30 min', 45 => '45 min', 60 => '1 h', 90 => '1 h 30', 120 => '2 h', 180 => '3 h', 240 => '4 h']" />
            </div>
            <x-ui.input label="Lieu" wire:model="form.location" />
            <x-ui.select label="Statut" wire:model="form.status" :options="collect(config('erp.statuses.appointment'))->map(fn ($s) => $s[0])" />
            <x-ui.textarea label="Notes" wire:model="form.notes" rows="2" />
        </div>
        <x-slot:footer>
            @if ($editing)<x-ui.button variant="ghost" icon="trash" class="mr-auto text-flame-600" wire:click="delete" wire:confirm="Supprimer ce rendez-vous ?">Supprimer</x-ui.button>@endif
            @if ($editing && auth()->user()->canManage('reception') && in_array($form['status'] ?? '', ['scheduled', 'confirmed'], true) && ($form['date'] ?? '') === today()->toDateString())
                <x-ui.button variant="secondary" icon="arrow-right-end-on-rectangle" wire:click="arrive">Visiteur arrivé</x-ui.button>
            @endif
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
