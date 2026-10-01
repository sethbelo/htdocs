<div>
    @php $canManage = auth()->user()->canManage('reception'); @endphp
    <x-ui.page-header title="Courrier" eyebrow="Réception" subtitle="Registre du courrier entrant et sortant : référence, destinataire, circuit de traitement et scan.">
        <x-slot:actions>
            <a href="{{ route('reception.mail.print', $this->filters()) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="printer" class="size-[18px]" /> Imprimer le registre</a>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="paper-airplane" wire:click="newMail('out')">Courrier sortant</x-ui.button>
                <x-ui.button variant="accent" icon="inbox-arrow-down" wire:click="newMail('in')">Courrier entrant</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Entrants (mois)" :value="$stats['in']" icon="inbox-arrow-down" />
        <x-ui.stat label="Sortants (mois)" :value="$stats['out']" icon="paper-airplane" tone="lagoon" />
        <x-ui.stat label="À transmettre" :value="$stats['registered']" icon="envelope" tone="flame" />
        <x-ui.stat label="En cours de traitement" :value="$stats['forwarded']" icon="clock" tone="sun" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-line p-3 xl:flex-row xl:flex-wrap xl:items-center">
            <div class="segmented self-start">
                <button type="button" wire:click="$set('direction', '')" aria-pressed="{{ $direction === '' ? 'true' : 'false' }}">Tous</button>
                <button type="button" wire:click="$set('direction', 'in')" aria-pressed="{{ $direction === 'in' ? 'true' : 'false' }}">Entrants</button>
                <button type="button" wire:click="$set('direction', 'out')" aria-pressed="{{ $direction === 'out' ? 'true' : 'false' }}">Sortants</button>
            </div>
            <select wire:model.live="status" class="input h-9 w-auto" aria-label="Statut"><option value="">Tous statuts</option>@foreach (config('erp.statuses.mail') as $k => [$l])<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <select wire:model.live="department" class="input h-9 w-auto" aria-label="Service"><option value="">Tous services</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
            <label class="flex items-center gap-1.5 text-[13px] text-fg-muted">Du <input type="date" wire:model.live="from" class="input h-9 w-auto"></label>
            <label class="flex items-center gap-1.5 text-[13px] text-fg-muted">au <input type="date" wire:model.live="to" class="input h-9 w-auto"></label>
            @if ($this->filters())<x-ui.button variant="ghost" size="sm" icon="x-mark" wire:click="resetFilters">Effacer</x-ui.button>@endif
            <x-ui.search wire:model.live.debounce.300ms="search" placeholder="Référence, correspondant, objet…" class="xl:ml-auto" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Référence</th><th>Date</th><th>Correspondant</th><th>Objet</th><th>Destinataire</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($mails as $m)
                        <tr wire:key="m-{{ $m->id }}">
                            <td class="whitespace-nowrap"><span class="flex items-center gap-1.5 font-semibold"><x-ui.icon :name="$m->direction === 'in' ? 'inbox-arrow-down' : 'paper-airplane'" class="size-4 text-fg-subtle" />{{ $m->reference }}</span></td>
                            <td class="whitespace-nowrap text-fg-muted">{{ $m->record_date->format('d/m/Y') }}</td>
                            <td>{{ $m->correspondent }}<p class="text-[11.5px] text-fg-subtle">{{ ['physique' => 'Dépôt physique', 'email' => 'E-mail', 'coursier' => 'Coursier'][$m->channel] ?? $m->channel }}</p></td>
                            <td class="max-w-[280px] truncate">{{ $m->subject }}</td>
                            <td class="text-fg-muted">{{ $m->department?->name ?? '—' }}<p class="text-[11.5px] text-fg-subtle">{{ $m->handler?->name }}</p></td>
                            <td><x-ui.status map="mail" :value="$m->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                @if ($m->file_path)<a href="{{ route('reception.mail.file', $m) }}" class="btn btn-ghost btn-sm btn-icon" title="Scan joint"><x-ui.icon name="paper-clip" class="size-4" /></a>@endif
                                @if ($canManage)
                                    @if ($next = \App\Livewire\Reception\Mail::MAIL_FLOW[$m->status] ?? null)
                                        <x-ui.button variant="soft" size="sm" wire:click="advanceMail({{ $m->id }})">{{ ['forwarded' => 'Transmettre', 'processed' => 'Traité', 'archived' => 'Archiver'][$next] }}</x-ui.button>
                                    @endif
                                    <x-ui.button variant="ghost" size="sm" square icon="pencil-square" wire:click="editMail({{ $m->id }})" aria-label="Modifier" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty icon="envelope" title="Aucun courrier" text="Aucun courrier ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($mails->hasPages())<div class="border-t border-line px-4 py-3">{{ $mails->links('components.ui.pagination') }}</div>@endif
    </div>

    @include('livewire.reception.partials.mail-drawer')
</div>
