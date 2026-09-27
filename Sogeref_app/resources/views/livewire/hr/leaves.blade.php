<div>
    <x-ui.page-header title="Congés" eyebrow="Ressources humaines" subtitle="Demandes, validation, soldes (jours ouvrables, dimanches exclus) et planning des absences.">
        <x-slot:actions>
            @if (auth()->user()->canManage('leaves'))<x-ui.button variant="accent" icon="plus" wire:click="create">Nouvelle demande</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="En attente de validation" :value="$counts['pending'] ?? 0" icon="clock" tone="sun" />
        <x-ui.stat label="Approuvées" :value="$counts['approved'] ?? 0" icon="check-badge" tone="emerald" />
        <x-ui.stat label="Refusées" :value="$counts['rejected'] ?? 0" icon="x-circle" tone="flame" />
        <x-ui.stat label="Absents aujourd’hui" :value="$onLeaveToday->count()" icon="sun" tone="lagoon" :hint="$onLeaveToday->pluck('employee.full_name')->take(2)->implode(', ')" />
    </div>

    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-1 border-b border-line p-3">
            @foreach (['pending' => 'En attente', 'approved' => 'Approuvées', 'rejected' => 'Refusées', '' => 'Toutes'] as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')" @class(['btn btn-sm', 'btn-soft' => $status === $key, 'btn-ghost' => $status !== $key])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Employé</th><th>Type</th><th>Période</th><th class="num">Jours</th><th class="num">Solde</th><th>Motif</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr wire:key="lv-{{ $r->id }}">
                            <td><div class="flex items-center gap-2.5"><x-ui.avatar :name="$r->employee->full_name" size="sm" /><div><p class="font-semibold">{{ $r->employee->full_name }}</p><p class="text-[12px] text-fg-subtle">{{ $r->employee->department?->name }}</p></div></div></td>
                            <td><span class="inline-flex items-center gap-2"><span class="size-2 rounded-full" style="background: {{ $r->type->color }}"></span>{{ $r->type->name }}</span></td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $r->start_date->format('d/m') }} → {{ $r->end_date->format('d/m/Y') }}</td>
                            <td class="num font-semibold">{{ qty($r->days) }}</td>
                            <td class="num text-fg-muted">{{ qty($r->employee->leave_balance) }}</td>
                            <td class="max-w-[220px] truncate text-fg-muted">{{ $r->reason }}</td>
                            <td><x-ui.status map="leave" :value="$r->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                @if ($r->status === 'pending' && (auth()->user()->isMaster() || auth()->user()->can('leaves.approve')))
                                    <x-ui.button variant="ghost" size="sm" icon="x-mark" wire:click="decide({{ $r->id }}, false)" wire:confirm="Refuser cette demande ?">Refuser</x-ui.button>
                                    <x-ui.button variant="success" size="sm" icon="check" wire:click="decide({{ $r->id }}, true)">Approuver</x-ui.button>
                                @else
                                    <span class="text-[12px] text-fg-subtle">{{ $r->approver?->name }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty icon="sun" title="Aucune demande" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())<div class="border-t border-line px-4 py-3">{{ $requests->links('components.ui.pagination') }}</div>@endif
    </div>

    {{-- ═══ CALENDRIER DES ABSENCES ══════════════════════════════════════════ --}}
    <x-ui.card title="Planning des absences" icon="calendar-days" :padded="false">
        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" square icon="chevron-left" wire:click="shiftMonth(-1)" />
            <span class="min-w-32 text-center text-[13.5px] font-semibold">{{ ucfirst($monthStart->translatedFormat('F Y')) }}</span>
            <x-ui.button variant="ghost" size="sm" square icon="chevron-right" wire:click="shiftMonth(1)" />
        </x-slot:actions>
        <div class="table-wrap">
            <table class="w-full min-w-[900px] border-collapse text-[11.5px]">
                <thead><tr><th class="sticky left-0 bg-surface-2 px-3 py-2 text-left font-semibold text-fg-subtle">Employé</th>@foreach ($days as $d)<th @class(['w-7 py-2 text-center font-semibold', 'bg-ink-100 text-fg-subtle dark:bg-white/5' => $d->isSunday(), 'text-sun-700' => $d->isToday()])>{{ $d->format('d') }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse ($calendar as $employeeId => $leaves)
                        <tr class="border-t border-line">
                            <td class="sticky left-0 bg-surface px-3 py-2 font-medium whitespace-nowrap">{{ $leaves->first()->employee->full_name }}</td>
                            @foreach ($days as $d)
                                @php $leave = $leaves->first(fn ($l) => $d->between($l->start_date, $l->end_date)); @endphp
                                <td @class(['h-8 border-l border-line/50', 'bg-ink-100 dark:bg-white/5' => $d->isSunday() && ! $leave])>@if ($leave)<span class="block h-5 rounded-sm" style="background: {{ $leave->type->color }}" title="{{ $leave->type->name }}"></span>@endif</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $days->count() + 1 }}" class="px-3 py-8 text-center text-fg-subtle">Aucune absence approuvée ce mois-ci.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-4 border-t border-line px-4 py-3 text-[12px]">@foreach ($types as $t)<span class="flex items-center gap-1.5"><span class="size-3 rounded-sm" style="background: {{ $t->color }}"></span>{{ $t->name }}</span>@endforeach</div>
    </x-ui.card>

    <x-ui.drawer wire:model="showForm" title="Demande de congé" icon="sun">
        <div class="space-y-4">
            <x-ui.select label="Employé" wire:model="employeeId" placeholder="Choisir…" :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' — solde '.qty($e->leave_balance).' j'])" required />
            <x-ui.select label="Type de congé" wire:model="leaveTypeId" :options="$types->pluck('name', 'id')" />
            <div class="grid grid-cols-2 gap-3"><x-ui.input label="Du" type="date" wire:model="from" /><x-ui.input label="Au (inclus)" type="date" wire:model="to" /></div>
            <x-ui.textarea label="Motif" wire:model="reason" rows="3" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="paper-airplane" wire:click="save">Soumettre</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
