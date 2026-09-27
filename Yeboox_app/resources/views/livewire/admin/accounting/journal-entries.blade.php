<div class="p-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Journal Comptable</h1>
        <p class="text-sm text-slate-500 mt-1">Écritures comptables — Partie double OHADA</p>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total écritures</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $totalEntries }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Validées</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $postedEntries }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Brouillons</p>
            <p class="text-2xl font-bold text-amber-500 mt-1">{{ $draftEntries }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total mouvements</p>
            <p class="text-2xl font-bold text-violet-600 mt-1">{{ number_format($totalDebit, 0, ',', '.') }} <span class="text-sm font-normal text-slate-400">FC</span></p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="flex flex-wrap gap-3">
        <flux:input class="min-w-[200px] flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="N°, description ou référence…" clearable />
        <flux:select wire:model.live="filterJournal" class="sm:w-56">
            <flux:select.option value="">Tous les journaux</flux:select.option>
            @foreach($journals as $journal)
                <flux:select.option value="{{ $journal->code }}">{{ $journal->code }} — {{ $journal->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterStatus" class="sm:w-44">
            <flux:select.option value="">Tous les statuts</flux:select.option>
            <flux:select.option value="draft">Brouillon</flux:select.option>
            <flux:select.option value="posted">Validée</flux:select.option>
            <flux:select.option value="cancelled">Annulée</flux:select.option>
        </flux:select>
    </flux:card>

    {{-- Flash --}}
    @if(session('error'))
        <x-dismissible-alert variant="danger" icon="exclamation-triangle">{{ session('error') }}</x-dismissible-alert>
    @endif

    {{-- Entries List --}}
    <div class="space-y-3">
        @foreach($entries as $entry)
            @php
                $statusStyles = match($entry->status) {
                    'posted' => ['bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400', 'Validée'],
                    'draft' => ['bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400', 'Brouillon'],
                    'cancelled' => ['bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400', 'Annulée'],
                    default => ['bg-slate-100 text-slate-600', '—'],
                };
            @endphp
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                {{-- Entry Header --}}
                <div class="px-5 py-3 flex items-center justify-between border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded-lg bg-violet-100 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400 text-xs font-bold font-mono">
                            {{ $entry->entry_number }}
                        </span>
                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-bold">
                            {{ $entry->journal->code }}
                        </span>
                        <span class="text-sm text-slate-900 dark:text-white font-medium">{{ $entry->description }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">{{ $entry->entry_date->format('d/m/Y') }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyles[0] }}">{{ $statusStyles[1] }}</span>
                    </div>
                </div>

                {{-- Entry Lines --}}
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] text-slate-400 uppercase tracking-wider">
                            <th class="px-5 py-2 text-left w-24">Compte</th>
                            <th class="px-5 py-2 text-left">Libellé</th>
                            <th class="px-5 py-2 text-right w-36">Débit</th>
                            <th class="px-5 py-2 text-right w-36">Crédit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        @foreach($entry->lines as $line)
                            <tr>
                                <td class="px-5 py-2">
                                    <span class="font-mono text-xs text-violet-600 dark:text-violet-400">{{ $line->account->code }}</span>
                                </td>
                                <td class="px-5 py-2 text-slate-700 dark:text-slate-300">
                                    {{ $line->label ?: $line->account->name }}
                                </td>
                                <td class="px-5 py-2 text-right font-mono {{ $line->debit > 0 ? 'text-blue-600 font-medium' : 'text-slate-300 dark:text-slate-600' }}">
                                    {{ $line->debit > 0 ? number_format($line->debit, 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-5 py-2 text-right font-mono {{ $line->credit > 0 ? 'text-emerald-600 font-medium' : 'text-slate-300 dark:text-slate-600' }}">
                                    {{ $line->credit > 0 ? number_format($line->credit, 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Entry Footer --}}
                <div class="px-5 py-2.5 bg-slate-50 dark:bg-slate-700/30 flex items-center justify-between border-t border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        @if($entry->reference)
                            <span>Réf: {{ $entry->reference }}</span>
                        @endif
                        <span>Par {{ $entry->creator?->name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($entry->status === 'draft')
                            <button wire:click="postEntry('{{ $entry->id }}')" wire:confirm="Valider cette écriture ?"
                                    class="px-3 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors">
                                ✓ Valider
                            </button>
                            <button wire:click="cancelEntry('{{ $entry->id }}')" wire:confirm="Annuler cette écriture ?"
                                    class="px-3 py-1 rounded-lg bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs hover:bg-slate-300 transition-colors">
                                Annuler
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <flux:pagination :paginator="$entries" scroll-to />

    @if($entries->isEmpty())
        <div class="text-center py-20 text-slate-400">
            <x-heroicon-o-document-text class="w-16 h-16 mx-auto mb-4 opacity-30"/>
            <p class="text-lg font-medium">Aucune écriture comptable</p>
        </div>
    @endif
</div>
