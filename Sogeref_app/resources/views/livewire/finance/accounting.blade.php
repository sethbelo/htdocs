<div>
    <x-ui.page-header title="Comptabilité" eyebrow="Finance · SYSCOHADA" subtitle="Écritures générées automatiquement par les ventes, la caisse, les bons, la paie et les notes de frais.">
        <x-slot:actions>
            <a href="{{ route('pdf.report', ['report' => 'balance', 'from' => $from, 'to' => $to]) }}" target="_blank" class="btn btn-secondary"><x-ui.icon name="printer" class="size-[18px]" /> Balance PDF</a>
            @if (auth()->user()->isMaster() || auth()->user()->can('accounting.post'))<x-ui.button variant="accent" icon="plus" wire:click="newEntry">Écriture manuelle</x-ui.button>@endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.stat label="Écritures de la période" :value="$kpis['entries']" icon="document-duplicate" />
        <x-ui.stat label="Total débit" :value="money($kpis['debit'], 'USD', 0)" icon="arrow-down-left" tone="lagoon" />
        <x-ui.stat label="Total crédit" :value="money($kpis['credit'], 'USD', 0)" icon="arrow-up-right" tone="sun" :hint="abs($kpis['debit'] - $kpis['credit']) < 0.01 ? 'Balance équilibrée ✓' : 'Écart : '.money($kpis['debit'] - $kpis['credit'])" />
        <x-ui.stat label="Résultat (classes 7 − 6)" :value="money($kpis['result'], 'USD', 0)" icon="scale" :tone="$kpis['result'] >= 0 ? 'emerald' : 'flame'" />
    </div>

    <div class="card">
        <div class="px-4"><x-ui.tabs :current="$tab" :tabs="['journal' => ['label' => 'Journaux', 'icon' => 'book-open'], 'grand-livre' => ['label' => 'Grand livre', 'icon' => 'queue-list'], 'balance' => ['label' => 'Balance', 'icon' => 'scale'], 'plan' => ['label' => 'Plan comptable', 'icon' => 'list-bullet']]" /></div>
        @if ($tab !== 'plan')
            <div class="flex flex-col gap-2 border-b border-line p-3 md:flex-row md:items-center">
                <input type="date" wire:model.live="from" class="input md:w-44"><span class="hidden text-fg-subtle md:block">→</span><input type="date" wire:model.live="to" class="input md:w-44">
                @if ($tab === 'journal')
                    <select wire:model.live="journalId" class="input md:w-56"><option value="">Tous les journaux</option>@foreach ($journals as $j)<option value="{{ $j->id }}">{{ $j->code }} · {{ $j->name }}</option>@endforeach</select>
                    <x-ui.search wire:model.live.debounce.300ms="search" placeholder="N° ou libellé…" class="md:ml-auto" />
                @elseif ($tab === 'grand-livre')
                    <select wire:model.live="accountId" class="input md:w-80">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach</select>
                @endif
            </div>
        @endif

        @if ($tab === 'journal')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th>Journal</th><th>Pièce</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($entries as $e)
                            <tr wire:key="je-{{ $e->id }}" class="cursor-pointer" wire:click="$set('expanded', {{ $expanded === $e->id ? 'null' : $e->id }})">
                                <td class="whitespace-nowrap text-fg-muted">{{ $e->entry_date->format('d/m/Y') }}</td>
                                <td><x-ui.badge tone="brand">{{ $e->journal->code }}</x-ui.badge></td>
                                <td class="text-[12.5px] font-semibold">{{ $e->number }}</td>
                                <td class="max-w-[360px] truncate">{{ $e->description }}</td>
                                <td class="num">{{ money($e->lines->sum('debit')) }}</td>
                                <td class="num">{{ money($e->lines->sum('credit')) }}</td>
                                <td><x-ui.icon :name="$expanded === $e->id ? 'chevron-up' : 'chevron-down'" class="size-4 text-fg-subtle" /></td>
                            </tr>
                            @if ($expanded === $e->id)
                                @foreach ($e->lines as $l)
                                    <tr class="bg-surface-2/70 text-[12.5px]"><td></td><td colspan="2" class="font-semibold text-brand-600 dark:text-lagoon-300">{{ $l->account->code }}</td><td class="text-fg-muted">{{ $l->account->name }} — {{ $l->label }}</td><td class="num">{{ (float) $l->debit ? money($l->debit) : '' }}</td><td class="num">{{ (float) $l->credit ? money($l->credit) : '' }}</td><td></td></tr>
                                @endforeach
                            @endif
                        @empty
                            <tr><td colspan="7"><x-ui.empty icon="book-open" title="Aucune écriture sur la période" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($entries->hasPages())<div class="border-t border-line px-4 py-3">{{ $entries->links('components.ui.pagination') }}</div>@endif

        @elseif ($tab === 'grand-livre')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Date</th><th>Journal</th><th>Pièce</th><th>Libellé</th><th>Tiers</th><th class="num">Débit</th><th class="num">Crédit</th><th class="num">Solde</th></tr></thead>
                    <tbody>
                        <tr class="bg-surface-2 font-semibold"><td colspan="7">Solde d’ouverture au {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }}</td><td class="num">{{ money($opening) }}</td></tr>
                        @forelse ($ledger as $l)
                            <tr>
                                <td class="whitespace-nowrap text-fg-muted">{{ $l->entry->entry_date->format('d/m/Y') }}</td>
                                <td>{{ $l->entry->journal->code }}</td>
                                <td class="text-[12.5px]">{{ $l->entry->number }}</td>
                                <td class="max-w-[280px] truncate">{{ $l->label }}</td>
                                <td class="max-w-[160px] truncate text-fg-muted">{{ $l->contact?->display_name }}</td>
                                <td class="num">{{ (float) $l->debit ? money($l->debit) : '' }}</td>
                                <td class="num">{{ (float) $l->credit ? money($l->credit) : '' }}</td>
                                <td class="num font-semibold {{ $l->running < 0 ? 'text-flame-600' : '' }}">{{ money($l->running) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-ui.empty icon="queue-list" title="Aucun mouvement sur ce compte" /></td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="bg-surface-2 font-semibold"><td colspan="5" class="px-4 py-3">Totaux de la période</td><td class="num px-4">{{ money($ledger->sum('debit')) }}</td><td class="num px-4">{{ money($ledger->sum('credit')) }}</td><td class="num px-4">{{ money($ledger->last()?->running ?? $opening) }}</td></tr></tfoot>
                </table>
            </div>

        @elseif ($tab === 'balance')
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>@foreach ($report['sections'][0]['columns'] as $i => $c)<th @class(['num' => $i > 1])>{{ $c }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($report['sections'][0]['rows'] as $row)
                            <tr>@foreach ($row as $i => $cell)<td @class(['num' => $i > 1, 'font-semibold text-brand-600 dark:text-lagoon-300' => $i === 0])>{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="flex justify-end border-b border-line p-3">@if (auth()->user()->canManage('accounting'))<x-ui.button variant="soft" size="sm" icon="plus" wire:click="editAccount">Nouveau compte</x-ui.button>@endif</div>
            <div class="grid gap-x-8 p-4 md:grid-cols-2">
                @foreach ($accounts->groupBy('class') as $class => $group)
                    <div class="mb-5">
                        <p class="eyebrow mb-2">Classe {{ $class }} — {{ ['1' => 'Ressources durables', '2' => 'Actif immobilisé', '3' => 'Stocks', '4' => 'Tiers', '5' => 'Trésorerie', '6' => 'Charges', '7' => 'Produits', '8' => 'Autres'][$class] ?? '' }}</p>
                        <ul class="divide-y divide-line rounded-xl border border-line">
                            @foreach ($group as $a)
                                <li class="flex items-center justify-between gap-3 px-3 py-2 text-[13px]">
                                    <span><span class="inline-block w-14 font-semibold text-brand-600 dark:text-lagoon-300">{{ $a->code }}</span>{{ $a->name }} @unless ($a->is_active)<span class="text-fg-subtle">(inactif)</span>@endunless</span>
                                    @if (auth()->user()->canManage('accounting'))<button type="button" wire:click="editAccount({{ $a->id }})" class="btn btn-ghost btn-sm btn-icon"><x-ui.icon name="pencil-square" class="size-4" /></button>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-ui.drawer wire:model="showEntry" title="Écriture manuelle" subtitle="Opérations diverses, régularisations, à-nouveaux" icon="pencil-square" size="xl">
        <div class="space-y-4">
            <div class="grid gap-3 md:grid-cols-3">
                <x-ui.select label="Journal" wire:model="entry.journal_id" :options="$journals->mapWithKeys(fn ($j) => [$j->id => $j->code.' · '.$j->name])" />
                <x-ui.input label="Date" type="date" wire:model="entry.entry_date" />
                <x-ui.input label="Libellé" wire:model="entry.description" required />
            </div>
            <table class="table min-w-[720px] rounded-xl border border-line">
                <thead><tr><th class="w-[38%]">Compte</th><th>Libellé</th><th class="w-32">Débit</th><th class="w-32">Crédit</th></tr></thead>
                <tbody>
                    @foreach ($entry['lines'] ?? [] as $i => $line)
                        <tr wire:key="jl-{{ $i }}">
                            <td><select wire:model="entry.lines.{{ $i }}.account_id" class="input"><option value="">Compte…</option>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach</select></td>
                            <td><input wire:model="entry.lines.{{ $i }}.label" class="input"></td>
                            <td><input type="number" step="0.01" wire:model.live.debounce.400ms="entry.lines.{{ $i }}.debit" class="input text-right"></td>
                            <td><input type="number" step="0.01" wire:model.live.debounce.400ms="entry.lines.{{ $i }}.credit" class="input text-right"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @php
                $d = collect($entry['lines'] ?? [])->sum(fn ($l) => (float) ($l['debit'] ?: 0));
                $c = collect($entry['lines'] ?? [])->sum(fn ($l) => (float) ($l['credit'] ?: 0));
            @endphp
            <div class="flex items-center justify-between">
                <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="addEntryLine">Ajouter une ligne</x-ui.button>
                <p @class(['rounded-lg px-3 py-1.5 text-[13px] font-semibold', 'bg-emerald-50 text-emerald-700' => abs($d - $c) < 0.01 && $d > 0, 'bg-flame-50 text-flame-700' => abs($d - $c) >= 0.01])>Débit {{ money($d) }} · Crédit {{ money($c) }} {{ abs($d - $c) < 0.01 && $d > 0 ? '✓ équilibrée' : '— écart '.money($d - $c) }}</p>
            </div>
            @error('entry.lines')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveEntry">Comptabiliser</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>

    <x-ui.drawer wire:model="showAccount" title="Compte comptable" icon="list-bullet">
        <div class="space-y-4">
            <x-ui.input label="Numéro" wire:model="account.code" required hint="Le premier chiffre détermine la classe SYSCOHADA." />
            <x-ui.input label="Intitulé" wire:model="account.name" required />
            <x-ui.select label="Nature" wire:model="account.type" :options="['asset' => 'Actif', 'liability' => 'Passif / dette', 'equity' => 'Capitaux propres', 'revenue' => 'Produit', 'expense' => 'Charge']" />
            <x-ui.toggle wire:model="account.is_active" label="Actif" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="saveAccount">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.drawer>
</div>
