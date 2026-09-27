<div class="space-y-6">
    <x-page-header title="Comptabilité générale" subtitle="Toutes les ventes POS, encaissements, achats et paies alimentent automatiquement les journaux.">
        <x-slot:actions>
            @can('accounting.export')<flux:button size="sm" icon="arrow-down-tray" wire:click="export">Balance Excel</flux:button>@endcan
            @can('accounting.create')<flux:button size="sm" variant="primary" icon="plus" wire:click="newEntry">Écriture manuelle</flux:button>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="flex flex-wrap items-center gap-2">
        @foreach (['journal' => 'Journaux', 'ledger' => 'Grand livre', 'balance' => 'Balance', 'income' => 'Compte de résultat', 'sheet' => 'Bilan', 'reconcile' => 'Rapprochement', 'chart' => 'Plan comptable'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
        <flux:spacer />
        <flux:input type="date" wire:model.live="from" size="sm" class="max-w-40" />
        <flux:input type="date" wire:model.live="to" size="sm" class="max-w-40" />
    </div>

    <div class="panel p-4">
        @if ($tab === 'journal')
            <flux:select wire:model.live="journalCode" size="sm" class="mb-3 max-w-xs">
                <flux:select.option value="">Tous les journaux</flux:select.option>
                @foreach ($journals as $journal)<flux:select.option :value="$journal->code">{{ $journal->code }} — {{ $journal->name }}</flux:select.option>@endforeach
            </flux:select>
            <div class="space-y-3">
                @forelse ($entries as $entry)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="e-{{ $entry->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <div><flux:badge size="sm">{{ $entry->journal->code }}</flux:badge> <span class="font-medium">{{ $entry->number }}</span> · {{ $entry->entry_date->format('d/m/Y') }} · {{ $entry->description }} @if ($entry->reference)<span class="text-zinc-500">· réf. {{ $entry->reference }}</span>@endif</div>
                            <x-money :amount="$entry->total_debit" class="font-medium" />
                        </div>
                        <table class="w-full text-sm">
                            @foreach ($entry->lines as $line)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="px-3 py-1.5 w-24 text-zinc-500">{{ $line->account->code }}</td>
                                    <td class="py-1.5">{{ $line->account->name }} <span class="text-xs text-zinc-500">{{ $line->label !== $entry->description ? '· '.$line->label : '' }}</span></td>
                                    <td class="px-3 py-1.5 w-32 text-right tabular-nums">{{ (float) $line->debit ? \App\Services\Support\Money::format($line->debit) : '' }}</td>
                                    <td class="px-3 py-1.5 w-32 text-right tabular-nums">{{ (float) $line->credit ? \App\Services\Support\Money::format($line->credit) : '' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @empty
                    <x-empty-state icon="book-open" title="Aucune écriture sur la période" />
                @endforelse
            </div>
            <div class="mt-3">{{ $entries?->links() }}</div>
        @elseif ($tab === 'ledger')
            <flux:select wire:model.live="accountId" class="mb-3 max-w-md" placeholder="Choisir un compte…">
                <flux:select.option value="">Choisir un compte…</flux:select.option>
                @foreach ($accounts as $account)<flux:select.option :value="$account->id">{{ $account->code }} — {{ $account->name }}</flux:select.option>@endforeach
            </flux:select>
            <flux:table>
                <flux:table.columns><flux:table.column>Date</flux:table.column><flux:table.column>Pièce</flux:table.column><flux:table.column>Libellé</flux:table.column><flux:table.column align="end">Débit</flux:table.column><flux:table.column align="end">Crédit</flux:table.column><flux:table.column align="end">Solde</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($ledger as $row)
                        <flux:table.row>
                            <flux:table.cell>{{ $row->date }}</flux:table.cell>
                            <flux:table.cell class="text-xs">{{ $row->number }} {{ $row->reference }}</flux:table.cell>
                            <flux:table.cell>{{ $row->label }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row->debit ? \App\Services\Support\Money::format($row->debit) : '' }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row->credit ? \App\Services\Support\Money::format($row->credit) : '' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium"><x-money :amount="$row->running" /></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @elseif ($tab === 'balance')
            <flux:table>
                <flux:table.columns><flux:table.column>Compte</flux:table.column><flux:table.column>Intitulé</flux:table.column><flux:table.column align="end">Débit</flux:table.column><flux:table.column align="end">Crédit</flux:table.column><flux:table.column align="end">Solde débiteur</flux:table.column><flux:table.column align="end">Solde créditeur</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($trialBalance as $row)
                        <flux:table.row>
                            <flux:table.cell>{{ $row->account->code }}</flux:table.cell>
                            <flux:table.cell>{{ $row->account->name }}</flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$row->debit" /></flux:table.cell>
                            <flux:table.cell align="end"><x-money :amount="$row->credit" /></flux:table.cell>
                            <flux:table.cell align="end">{{ $row->balance > 0 ? \App\Services\Support\Money::format($row->balance) : '' }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row->balance < 0 ? \App\Services\Support\Money::format(-$row->balance) : '' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                    <flux:table.row class="font-semibold">
                        <flux:table.cell colspan="2">Totaux</flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$trialBalance->sum('debit')" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$trialBalance->sum('credit')" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="$trialBalance->where('balance', '>', 0)->sum('balance')" /></flux:table.cell>
                        <flux:table.cell align="end"><x-money :amount="-$trialBalance->where('balance', '<', 0)->sum('balance')" /></flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        @elseif ($tab === 'income')
            <div class="grid gap-6 md:grid-cols-2">
                @foreach (['expenses' => ['Charges', 'total_expenses'], 'income' => ['Produits', 'total_income']] as $key => [$title, $totalKey])
                    <div>
                        <flux:heading>{{ $title }}</flux:heading>
                        <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            @foreach ($incomeStatement[$key] as $row)
                                <li class="flex justify-between py-1.5"><span>{{ $row->account->code }} {{ $row->account->name }}</span><x-money :amount="$row->amount" /></li>
                            @endforeach
                            <li class="flex justify-between py-2 font-semibold"><span>Total {{ Str::lower($title) }}</span><x-money :amount="$incomeStatement[$totalKey]" /></li>
                        </ul>
                    </div>
                @endforeach
            </div>
            <div @class(['mt-4 rounded-lg p-4 text-center text-lg font-semibold', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10' => $incomeStatement['result'] >= 0, 'bg-red-50 text-red-700 dark:bg-red-500/10' => $incomeStatement['result'] < 0])>
                Résultat de la période : <x-money :amount="$incomeStatement['result']" />
            </div>
        @elseif ($tab === 'sheet')
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <flux:heading>Actif</flux:heading>
                    <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @foreach ($balanceSheet['assets'] as $row)<li class="flex justify-between py-1.5"><span>{{ $row->account->code }} {{ $row->account->name }}</span><x-money :amount="$row->amount" /></li>@endforeach
                        <li class="flex justify-between py-2 font-semibold"><span>Total actif</span><x-money :amount="$balanceSheet['total_assets']" /></li>
                    </ul>
                </div>
                <div>
                    <flux:heading>Passif & capitaux propres</flux:heading>
                    <ul class="mt-2 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        @foreach ($balanceSheet['liabilities'] as $row)<li class="flex justify-between py-1.5"><span>{{ $row->account->code }} {{ $row->account->name }}</span><x-money :amount="$row->amount" /></li>@endforeach
                        <li class="flex justify-between py-1.5"><span>Résultat de l’exercice</span><x-money :amount="$balanceSheet['result']" /></li>
                        <li class="flex justify-between py-2 font-semibold"><span>Total passif</span><x-money :amount="$balanceSheet['total_liabilities']" /></li>
                    </ul>
                </div>
            </div>
        @elseif ($tab === 'reconcile')
            <flux:text class="mb-2">Lignes de trésorerie (classe 5) non rapprochées avec les relevés bancaires / Mobile Money.</flux:text>
            <flux:table>
                <flux:table.columns><flux:table.column>Date</flux:table.column><flux:table.column>Compte</flux:table.column><flux:table.column>Libellé</flux:table.column><flux:table.column align="end">Débit</flux:table.column><flux:table.column align="end">Crédit</flux:table.column><flux:table.column></flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($unreconciled as $line)
                        <flux:table.row :key="$line->id">
                            <flux:table.cell>{{ $line->entry->entry_date->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>{{ $line->account->code }}</flux:table.cell>
                            <flux:table.cell>{{ $line->label }}</flux:table.cell>
                            <flux:table.cell align="end">{{ (float) $line->debit ? \App\Services\Support\Money::format($line->debit) : '' }}</flux:table.cell>
                            <flux:table.cell align="end">{{ (float) $line->credit ? \App\Services\Support\Money::format($line->credit) : '' }}</flux:table.cell>
                            <flux:table.cell>@can('accounting.validate')<flux:button size="xs" icon="check" wire:click="reconcile({{ $line->id }})">Rapprocher</flux:button>@endcan</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            @can('accounting.update')
                <form wire:submit="saveAccount" class="mb-4 grid gap-2 md:grid-cols-4">
                    <flux:input wire:model="newAccount.code" placeholder="N° de compte" />
                    <flux:input wire:model="newAccount.name" placeholder="Intitulé" class="md:col-span-2" />
                    <div class="flex gap-2">
                        <flux:select wire:model="newAccount.type">@foreach ($types as $v => $l)<flux:select.option :value="$v">{{ $l }}</flux:select.option>@endforeach</flux:select>
                        <flux:button type="submit" icon="plus" />
                    </div>
                </form>
            @endcan
            <flux:table>
                <flux:table.columns><flux:table.column>Compte</flux:table.column><flux:table.column>Intitulé</flux:table.column><flux:table.column>Classe</flux:table.column><flux:table.column>Nature</flux:table.column></flux:table.columns>
                <flux:table.rows>
                    @foreach ($accounts as $account)
                        <flux:table.row :key="$account->id"><flux:table.cell class="font-medium">{{ $account->code }}</flux:table.cell><flux:table.cell>{{ $account->name }}</flux:table.cell><flux:table.cell>{{ $account->class }}</flux:table.cell><flux:table.cell>{{ $account->type->label() }}</flux:table.cell></flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <flux:modal name="entry" class="w-full max-w-3xl">
        <form wire:submit="saveEntry" class="space-y-4">
            <flux:heading size="lg">Écriture manuelle</flux:heading>
            <div class="grid gap-3 md:grid-cols-4">
                <flux:select wire:model="entry.journal" label="Journal">@foreach ($journals as $j)<flux:select.option :value="$j->code">{{ $j->code }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="entry.date" type="date" label="Date" />
                <flux:input wire:model="entry.reference" label="Référence" />
                <flux:input wire:model="entry.description" label="Libellé" />
            </div>
            @foreach ($lines as $i => $line)
                <div class="grid grid-cols-12 gap-2" wire:key="el-{{ $i }}">
                    <flux:select wire:model="lines.{{ $i }}.account_id" class="col-span-5"><flux:select.option value="">Compte…</flux:select.option>@foreach ($accounts as $a)<flux:select.option :value="$a->id">{{ $a->code }} {{ $a->name }}</flux:select.option>@endforeach</flux:select>
                    <flux:input wire:model="lines.{{ $i }}.label" placeholder="Libellé" class="col-span-3" />
                    <flux:input wire:model.live.debounce.400ms="lines.{{ $i }}.debit" type="number" step="0.01" placeholder="Débit" class="col-span-2" />
                    <flux:input wire:model.live.debounce.400ms="lines.{{ $i }}.credit" type="number" step="0.01" placeholder="Crédit" class="col-span-2" />
                </div>
            @endforeach
            @php($d = collect($lines)->sum(fn ($l) => (float) $l['debit']))
            @php($c = collect($lines)->sum(fn ($l) => (float) $l['credit']))
            <div class="flex items-center justify-between">
                <flux:button type="button" size="sm" icon="plus" wire:click="addLine">Ligne</flux:button>
                <span @class(['text-sm font-medium', 'text-emerald-600' => abs($d - $c) < 0.01 && $d > 0, 'text-red-600' => abs($d - $c) >= 0.01])>Débit {{ number_format($d, 2, ',', ' ') }} · Crédit {{ number_format($c, 2, ',', ' ') }}</span>
            </div>
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Annuler</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Passer l’écriture</flux:button></div>
        </form>
    </flux:modal>
</div>
