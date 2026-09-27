<div>
    <x-ui.page-header title="Feuilles de temps" eyebrow="Services" subtitle="Heures passées par projet (facturables ou internes), soumises chaque semaine au responsable.">
        <x-slot:actions>
            @if ($users->count() > 1)<select wire:model.live="userId" class="input w-56">@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>@endif
            <x-ui.button variant="secondary" square icon="chevron-left" wire:click="shift(-1)" />
            <x-ui.button variant="secondary" square icon="chevron-right" wire:click="shift(1)" />
        </x-slot:actions>
    </x-ui.page-header>

    @php
        $colTotals = $days->mapWithKeys(fn ($d) => [$d->toDateString() => collect($grid)->sum(fn ($row) => (float) ($row[$d->toDateString()] ?? 0))]);
        $total = $colTotals->sum();
    @endphp
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4">
            <div><p class="font-display text-[16px] font-semibold">Semaine du {{ $days->first()->translatedFormat('j F Y') }}</p><p class="text-[12.5px] text-fg-subtle">{{ qty($total) }} h saisies</p></div>
            <x-ui.status map="timesheet" :value="$weekStatus" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Projet</th>@foreach ($days as $d)<th class="text-center">{{ ucfirst($d->translatedFormat('D d')) }}</th>@endforeach<th class="num">Total</th></tr></thead>
                <tbody>
                    @foreach ($grid as $projectId => $row)
                        <tr wire:key="row-{{ $projectId }}">
                            <td class="font-medium">{{ $projectId ? ($projects[$projectId]->name ?? 'Projet') : 'Tâches internes' }}</td>
                            @foreach ($days as $d)
                                <td class="w-24"><input type="number" step="0.5" min="0" max="16" wire:model.live.debounce.500ms="grid.{{ $projectId }}.{{ $d->toDateString() }}" class="input h-10 text-center" @disabled(! $canEdit || $weekStatus === 'approved')></td>
                            @endforeach
                            <td class="num font-semibold">{{ qty(collect($row)->sum()) }}</td>
                        </tr>
                    @endforeach
                    @if (empty($grid))<tr><td colspan="{{ $days->count() + 2 }}" class="py-8 text-center text-fg-subtle">Ajoutez un projet pour saisir vos heures.</td></tr>@endif
                </tbody>
                <tfoot><tr class="bg-surface-2 font-semibold"><td class="px-4 py-3">Total</td>@foreach ($colTotals as $t)<td @class(['px-4 text-center', 'text-flame-600' => $t > 10])>{{ qty($t) }}</td>@endforeach<td class="num px-4">{{ qty($total) }}</td></tr></tfoot>
            </table>
        </div>
        @if ($canEdit && $weekStatus !== 'approved')
            <div class="flex flex-col gap-2 border-t border-line p-3 md:flex-row md:items-center">
                <select wire:model="addProjectId" class="input md:w-80"><option value="">Ajouter un projet…</option><option value="0">Tâches internes</option>@foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->code }} · {{ $p->name }}</option>@endforeach</select>
                <x-ui.button variant="secondary" icon="plus" wire:click="addProject">Ajouter</x-ui.button>
                <div class="flex gap-2 md:ml-auto">
                    <x-ui.button variant="secondary" icon="document" wire:click="save">Enregistrer</x-ui.button>
                    <x-ui.button variant="primary" icon="paper-airplane" wire:click="submit">Soumettre</x-ui.button>
                </div>
            </div>
        @elseif ($weekStatus === 'submitted' && auth()->user()->canManage('timesheets'))
            <div class="flex justify-end border-t border-line p-3"><x-ui.button variant="success" icon="check" wire:click="approve">Approuver la semaine</x-ui.button></div>
        @endif
    </div>

    @if ($pending->isNotEmpty())
        <x-ui.card title="À approuver" icon="clock" :padded="false" class="mt-6">
            <ul>@foreach ($pending as $uid => $entries)<li class="flex items-center justify-between border-b border-line px-5 py-3 last:border-0"><span class="flex items-center gap-2.5"><x-ui.avatar :name="$entries->first()->user->name" size="sm" /><span class="font-medium">{{ $entries->first()->user->name }}</span></span><span class="text-[13px] text-fg-muted">{{ qty($entries->sum('hours')) }} h · <button type="button" wire:click="$set('userId', {{ $uid }})" class="link">Ouvrir</button></span></li>@endforeach</ul>
        </x-ui.card>
    @endif
</div>
