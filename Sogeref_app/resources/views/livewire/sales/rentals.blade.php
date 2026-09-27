<div>
    @php $canManage = auth()->user()->canManage('rentals'); @endphp
    <x-ui.page-header title="Location de matériel" eyebrow="Ventes" subtitle="Bétonnières, échafaudages, groupes électrogènes… Réservation, départ, retour et facturation.">
        <x-slot:actions>@if ($canManage)<x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle location</x-ui.button>@endif</x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="En cours" :value="$stats['ongoing']" icon="truck" tone="sun" />
        <x-ui.stat label="Réservations à venir" :value="$stats['reserved']" icon="calendar" tone="lagoon" />
        <x-ui.stat label="Retours en retard" :value="$stats['late']" icon="exclamation-triangle" :tone="$stats['late'] ? 'flame' : 'emerald'" />
        <x-ui.stat label="Chiffre du mois" :value="money($stats['month'], 'USD', 0)" icon="banknotes" />
    </div>

    {{-- ═══ PLANNING DE DISPONIBILITÉ ════════════════════════════════════════ --}}
    <x-ui.card title="Disponibilité du parc" subtitle="14 jours" icon="calendar-days" :padded="false" class="mb-6">
        <div class="table-wrap">
            <table class="w-full min-w-[900px] border-collapse text-[12px]">
                <thead><tr><th class="sticky left-0 bg-surface-2 px-3 py-2 text-left font-semibold text-fg-subtle">Équipement</th>@foreach ($days as $d)<th @class(['py-2 text-center font-semibold text-fg-subtle', 'text-sun-700 dark:text-sun-300' => $d->isToday()])>{{ ucfirst($d->translatedFormat('D')) }}<br>{{ $d->format('d') }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($fleet as $eq)
                        <tr class="border-t border-line">
                            <td class="sticky left-0 bg-surface px-3 py-2"><p class="font-semibold whitespace-nowrap">{{ $eq->name }}</p><p class="text-[11px] text-fg-subtle">{{ money($eq->daily_rent_price) }}/j · <x-ui.status map="equipment" :value="$eq->status" class="!h-5 !text-[10px]" /></p></td>
                            @foreach ($days as $d)
                                @php $b = $bookings->first(fn ($b) => $b['equipment_id'] === $eq->id && $d->between($b['start'], $b['end'])); @endphp
                                <td class="h-10 border-l border-line/50 p-0.5">@if ($b)<span @class(['block h-full rounded', 'bg-sun-400' => $b['status'] === 'ongoing', 'bg-lagoon-300' => $b['status'] === 'reserved']) title="{{ $b['number'] }}"></span>@elseif (in_array($eq->status, ['maintenance', 'out_of_service', 'assigned']))<span class="block h-full rounded bg-ink-100 dark:bg-white/5"></span>@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex gap-4 border-t border-line px-4 py-2.5 text-[12px] text-fg-muted"><span class="flex items-center gap-1.5"><span class="size-3 rounded bg-sun-400"></span>En location</span><span class="flex items-center gap-1.5"><span class="size-3 rounded bg-lagoon-300"></span>Réservé</span><span class="flex items-center gap-1.5"><span class="size-3 rounded bg-ink-100"></span>Indisponible (chantier / maintenance)</span></div>
    </x-ui.card>

    <div class="card overflow-hidden">
        <div class="flex gap-1 overflow-x-auto border-b border-line p-3 scrollbar-none">
            <button type="button" wire:click="$set('status', '')" @class(['btn btn-sm', 'btn-soft' => $status === '', 'btn-ghost' => $status !== ''])>Toutes</button>
            @foreach (config('erp.statuses.rental') as $k => [$l])<button type="button" wire:click="$set('status', '{{ $k }}')" @class(['btn btn-sm', 'btn-soft' => $status === $k, 'btn-ghost' => $status !== $k])>{{ $l }}</button>@endforeach
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Location</th><th>Client</th><th>Matériel</th><th>Période</th><th>Statut</th><th class="num">Montant</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rentals as $r)
                        <tr>
                            <td class="font-semibold">{{ $r->number }}</td>
                            <td class="max-w-[200px] truncate">{{ $r->contact->display_name }}</td>
                            <td class="max-w-[260px] text-[12.5px] text-fg-muted">{{ $r->lines->pluck('equipment.name')->implode(', ') }}</td>
                            <td @class(['whitespace-nowrap', 'font-semibold text-flame-600' => $r->isLate(), 'text-fg-muted' => ! $r->isLate()])>{{ $r->start_date->format('d/m') }} → {{ $r->end_date->format('d/m/Y') }}</td>
                            <td>@if ($r->isLate())<x-ui.badge tone="danger" dot>En retard</x-ui.badge>@else<x-ui.status map="rental" :value="$r->status" />@endif</td>
                            <td class="num font-semibold">{{ money($r->total) }}@if ((float) $r->deposit)<p class="text-[11px] font-normal text-fg-subtle">caution {{ money($r->deposit) }}</p>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($canManage)
                                    @if ($r->status === 'reserved')<x-ui.button variant="ghost" size="sm" wire:click="act({{ $r->id }}, 'cancel')">Annuler</x-ui.button><x-ui.button variant="primary" size="sm" icon="truck" wire:click="act({{ $r->id }}, 'start')">Départ</x-ui.button>@endif
                                    @if ($r->status === 'ongoing')<x-ui.button variant="secondary" size="sm" icon="arrow-uturn-left" wire:click="act({{ $r->id }}, 'return')">Retour</x-ui.button>@endif
                                    @if (in_array($r->status, ['ongoing', 'returned']) && ! $r->invoice_id)<x-ui.button variant="primary" size="sm" icon="document-currency-dollar" wire:click="act({{ $r->id }}, 'invoice')">Facturer</x-ui.button>@endif
                                @endif
                                @if ($r->invoice)<x-ui.button variant="ghost" size="sm" :href="route('documents.show', $r->invoice)">{{ $r->invoice->number }}</x-ui.button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty icon="key" title="Aucune location" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-ui.drawer wire:model="showForm" title="Nouvelle location" icon="key" size="lg">
        <div class="space-y-4">
            <x-ui.select label="Client" wire:model="contactId" placeholder="Choisir…" :options="$clients->mapWithKeys(fn ($c) => [$c->id => $c->display_name])" required />
            <div class="grid grid-cols-3 gap-3">
                <x-ui.input label="Départ" type="date" wire:model.live="startDate" />
                <x-ui.input label="Retour prévu" type="date" wire:model.live="endDate" />
                <x-ui.input label="Caution" type="number" step="0.01" wire:model="deposit" suffix="USD" />
            </div>
            <div>
                <p class="label">Matériel</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($fleet as $eq)
                        <label @class(['flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition', 'border-brand-600 bg-brand-50 dark:bg-white/6' => in_array($eq->id, $equipmentIds), 'border-line hover:border-line-strong' => ! in_array($eq->id, $equipmentIds), 'opacity-50' => in_array($eq->status, ['maintenance', 'out_of_service'])])>
                            <input type="checkbox" value="{{ $eq->id }}" wire:model.live="equipmentIds" class="checkbox" @disabled(in_array($eq->status, ['maintenance', 'out_of_service']))>
                            <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold">{{ $eq->name }}</span><span class="text-[12px] text-fg-subtle">{{ money($eq->daily_rent_price) }}/jour · {{ erp_status('equipment', $eq->status)[0] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <x-ui.textarea label="Notes (lieu d’utilisation, conditions)" wire:model="notes" rows="2" />
            <p class="rounded-xl bg-surface-2 px-4 py-3 text-[14px]">Montant estimé : <strong class="font-display text-[18px]">{{ money($estimate) }}</strong></p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save">Réserver</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
