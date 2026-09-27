<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Plan Comptable OHADA</h1>
            <p class="text-sm text-slate-500 mt-1">SYSCOHADA — Classes 1 à 9</p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="flex flex-wrap gap-3">
        <flux:input class="min-w-[200px] flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Code ou intitulé…" clearable />
        <flux:select wire:model.live="filterClass" class="sm:w-72">
            <flux:select.option value="0">Toutes les classes</flux:select.option>
            @for($i = 1; $i <= 9; $i++)
                <flux:select.option value="{{ $i }}">Classe {{ $i }} — {{ \App\Models\Account::classLabel($i) }}</flux:select.option>
            @endfor
        </flux:select>
    </flux:card>

    {{-- Account Classes --}}
    @foreach($grouped as $class => $accounts)
        @php
            $classColors = [
                1 => 'blue', 2 => 'purple', 3 => 'amber',
                4 => 'orange', 5 => 'emerald', 6 => 'red',
                7 => 'green', 8 => 'teal', 9 => 'slate',
            ];
            $color = $classColors[$class] ?? 'slate';
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            {{-- Class Header --}}
            <div class="px-5 py-3 bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-{{ $color }}-500 flex items-center justify-center text-white font-bold text-sm">{{ $class }}</span>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Classe {{ $class }}</h3>
                        <p class="text-xs text-slate-500">{{ \App\Models\Account::classLabel($class) }}</p>
                    </div>
                </div>
                <span class="px-2 py-1 rounded-lg bg-{{ $color }}-100 dark:bg-{{ $color }}-500/20 text-{{ $color }}-600 dark:text-{{ $color }}-400 text-xs font-bold">
                    {{ $accounts->count() }} comptes
                </span>
            </div>

            {{-- Accounts Table --}}
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <th class="px-5 py-2.5 w-24">Code</th>
                        <th class="px-5 py-2.5">Intitulé</th>
                        <th class="px-5 py-2.5 w-24 text-center">Type</th>
                        <th class="px-5 py-2.5 w-32 text-right">Solde</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @foreach($accounts->sortBy('code') as $account)
                        <tr class="{{ $account->is_group ? 'bg-slate-50 dark:bg-slate-700/30 font-semibold' : '' }}">
                            <td class="px-5 py-2.5">
                                <span class="font-mono text-sm {{ $account->is_group ? 'text-'.$color.'-600 dark:text-'.$color.'-400' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $account->code }}
                                </span>
                            </td>
                            <td class="px-5 py-2.5 text-slate-900 dark:text-white">
                                @if(!$account->is_group && $account->parent_id)
                                    <span class="text-slate-400 mr-1">└</span>
                                @endif
                                {{ $account->name }}
                            </td>
                            <td class="px-5 py-2.5 text-center">
                                @php
                                    $typeLabel = match($account->type) {
                                        'asset' => ['Actif', 'blue'],
                                        'liability' => ['Passif', 'orange'],
                                        'equity' => ['Capitaux', 'purple'],
                                        'revenue' => ['Produit', 'green'],
                                        'expense' => ['Charge', 'red'],
                                        default => ['—', 'slate'],
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $typeLabel[1] }}-100 dark:bg-{{ $typeLabel[1] }}-500/20 text-{{ $typeLabel[1] }}-600 dark:text-{{ $typeLabel[1] }}-400">
                                    {{ $typeLabel[0] }}
                                </span>
                            </td>
                            <td class="px-5 py-2.5 text-right font-mono text-sm">
                                @if(!$account->is_group)
                                    @php $balance = $account->balance; @endphp
                                    <span class="{{ $balance >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ number_format(abs($balance), 0, ',', '.') }} FC
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($grouped->isEmpty())
        <div class="text-center py-20 text-slate-400">
            <x-heroicon-o-calculator class="w-16 h-16 mx-auto mb-4 opacity-30"/>
            <p class="text-lg font-medium">Aucun compte trouvé</p>
        </div>
    @endif
</div>
