<div class="p-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Grand Livre</h1>
        <p class="text-sm text-slate-500 mt-1">Détail des mouvements par compte</p>
    </div>

    {{-- Totals --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total Débits</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($grandDebit, 0, ',', '.') }} <span class="text-sm font-normal text-slate-400">FC</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total Crédits</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($grandCredit, 0, ',', '.') }} <span class="text-sm font-normal text-slate-400">FC</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Comptes mouvementés</p>
            <p class="text-2xl font-bold text-violet-600 mt-1">{{ count($ledgerData) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="flex flex-wrap gap-3">
        <flux:select wire:model.live="filterAccount" class="min-w-[250px] flex-1">
            <flux:select.option value="">Tous les comptes</flux:select.option>
            @foreach($accounts as $account)
                <flux:select.option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="dateFrom" type="date" label="Du" />
        <flux:input wire:model.live="dateTo" type="date" label="Au" />
    </flux:card>

    {{-- Ledger Accounts --}}
    @foreach($ledgerData as $data)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            {{-- Account Header --}}
            <div class="px-5 py-3 bg-violet-50 dark:bg-violet-500/10 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="font-mono font-bold text-violet-600 dark:text-violet-400">{{ $data['account']->code }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $data['account']->name }}</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="text-blue-600 font-mono font-bold">D: {{ number_format($data['total_debit'], 0, ',', '.') }}</span>
                    <span class="text-emerald-600 font-mono font-bold">C: {{ number_format($data['total_credit'], 0, ',', '.') }}</span>
                    <span class="px-2 py-1 rounded-lg {{ $data['balance'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400' }} font-bold font-mono">
                        Solde: {{ number_format(abs($data['balance']), 0, ',', '.') }} {{ $data['balance'] >= 0 ? 'D' : 'C' }}
                    </span>
                </div>
            </div>

            {{-- Lines Table --}}
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <th class="px-5 py-2 text-left w-28">Date</th>
                        <th class="px-5 py-2 text-left w-28">N° Écriture</th>
                        <th class="px-5 py-2 text-left w-16">Journal</th>
                        <th class="px-5 py-2 text-left">Libellé</th>
                        <th class="px-5 py-2 text-right w-32">Débit</th>
                        <th class="px-5 py-2 text-right w-32">Crédit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @foreach($data['lines'] as $line)
                        <tr>
                            <td class="px-5 py-2 text-xs text-slate-500">{{ $line->entry->entry_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-2 text-xs font-mono text-violet-600 dark:text-violet-400">{{ $line->entry->entry_number }}</td>
                            <td class="px-5 py-2 text-xs font-bold text-slate-500">{{ $line->entry->journal->code }}</td>
                            <td class="px-5 py-2 text-slate-700 dark:text-slate-300">{{ $line->label ?: $line->entry->description }}</td>
                            <td class="px-5 py-2 text-right font-mono {{ $line->debit > 0 ? 'text-blue-600' : 'text-slate-300' }}">
                                {{ $line->debit > 0 ? number_format($line->debit, 0, ',', '.') : '' }}
                            </td>
                            <td class="px-5 py-2 text-right font-mono {{ $line->credit > 0 ? 'text-emerald-600' : 'text-slate-300' }}">
                                {{ $line->credit > 0 ? number_format($line->credit, 0, ',', '.') : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 dark:bg-slate-700/30 font-bold text-sm border-t border-slate-200 dark:border-slate-600">
                        <td colspan="4" class="px-5 py-2.5 text-right text-slate-500">Totaux</td>
                        <td class="px-5 py-2.5 text-right font-mono text-blue-600">{{ number_format($data['total_debit'], 0, ',', '.') }}</td>
                        <td class="px-5 py-2.5 text-right font-mono text-emerald-600">{{ number_format($data['total_credit'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endforeach

    @if(empty($ledgerData))
        <div class="text-center py-20 text-slate-400">
            <x-heroicon-o-book-open class="w-16 h-16 mx-auto mb-4 opacity-30"/>
            <p class="text-lg font-medium">Aucun mouvement trouvé</p>
            <p class="text-sm mt-1">Modifiez les filtres ou saisissez des écritures</p>
        </div>
    @endif
</div>
