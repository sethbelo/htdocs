<div>
    @php $canManage = auth()->user()->canManage('reception'); @endphp
    <x-ui.page-header title="Visites" eyebrow="Réception" subtitle="Registre des visiteurs : arrivées, départs, personnes visitées et badges.">
        <x-slot:actions>
            @if ($canManage)<x-ui.button variant="accent" icon="user-plus" wire:click="newVisitor">Enregistrer une arrivée</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Présents maintenant" :value="$stats['present']" icon="user-group" tone="sun" />
        <x-ui.stat label="Visites aujourd’hui" :value="$stats['today']" icon="identification" />
        <x-ui.stat label="Visites sur la période" :value="$stats['period']" icon="calendar" tone="lagoon" />
        <x-ui.stat label="Durée moyenne" :value="$stats['avg'] !== null ? $stats['avg'].' min' : '—'" icon="clock" tone="emerald" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 xl:flex-row xl:flex-wrap xl:items-center">
            <div class="flex gap-1">
                <x-ui.button variant="ghost" size="sm" wire:click="period('today')">Aujourd’hui</x-ui.button>
                <x-ui.button variant="ghost" size="sm" wire:click="period('week')">7 jours</x-ui.button>
                <x-ui.button variant="ghost" size="sm" wire:click="period('month')">Ce mois</x-ui.button>
            </div>
            <label class="flex items-center gap-1.5 text-[13px] text-fg-muted">Du <input type="date" wire:model.live="from" class="input h-9 w-auto"></label>
            <label class="flex items-center gap-1.5 text-[13px] text-fg-muted">au <input type="date" wire:model.live="to" class="input h-9 w-auto"></label>
            <select wire:model.live="state" class="input h-9 w-auto" aria-label="État"><option value="">Tous</option><option value="present">Présents</option><option value="left">Sortis</option></select>
            <select wire:model.live="host" class="input h-9 w-auto" aria-label="Personne visitée"><option value="">Toutes les personnes visitées</option>@foreach ($hosts as $h)<option value="{{ $h->id }}">{{ $h->name }}</option>@endforeach</select>
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Nom, société, badge…" class="xl:ml-auto" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Badge</th><th>Visiteur</th><th>Objet</th><th>Personne visitée</th><th>Arrivée</th><th>Départ</th><th></th></tr></thead>
                <tbody>
                    @forelse ($visits as $v)
                        <tr wire:key="v-{{ $v->id }}">
                            <td><x-ui.badge tone="warning">{{ $v->badge_number ?: '—' }}</x-ui.badge></td>
                            <td><p class="font-semibold">{{ $v->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ collect([$v->organization, $v->phone, $v->id_document])->filter()->implode(' · ') }}</p></td>
                            <td class="max-w-[260px] truncate">{{ $v->purpose }}@if ($v->appointment_id)<p class="text-[11.5px] text-fg-subtle">Sur rendez-vous</p>@endif</td>
                            <td class="text-fg-muted">{{ $v->host?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $v->checked_in_at->format('d/m H:i') }}</td>
                            <td class="whitespace-nowrap">@if ($v->checked_out_at){{ $v->checked_out_at->format('H:i') }} <span class="text-[11.5px] text-fg-subtle">({{ $v->checked_in_at->diffInMinutes($v->checked_out_at, true) }} min)</span>@else<x-ui.badge tone="success" dot>Présent</x-ui.badge>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('reception.visits.badge', $v) }}" target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Imprimer le badge"><x-ui.icon name="printer" class="size-4" /></a>
                                @if ($canManage && ! $v->checked_out_at)<x-ui.button variant="secondary" size="sm" icon="arrow-left-start-on-rectangle" wire:click="checkOut({{ $v->id }})">Sortie</x-ui.button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty icon="identification" title="Aucune visite" text="Aucune visite sur cette période." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())<div class="border-t border-line px-4 py-3">{{ $visits->links('components.ui.pagination') }}</div>@endif
    </div>

    @include('livewire.reception.partials.visitor-drawer')
</div>
