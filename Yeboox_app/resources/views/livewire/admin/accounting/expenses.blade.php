<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Dépenses</h1>
            <p class="text-sm text-slate-500 mt-1">Suivi et validation des dépenses</p>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus">Nouvelle dépense</flux:button>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Dépenses du mois</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalMonth, 0, ',', '.') }} <span class="text-sm font-normal text-slate-400">FC</span></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">En attente</p>
            <p class="text-2xl font-bold text-amber-500 mt-1">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Approuvées</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $totalApproved }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:card class="flex flex-wrap gap-3">
        <flux:input class="min-w-[200px] flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Rechercher…" clearable />
        <flux:select wire:model.live="filterCategory" class="sm:w-52">
            <flux:select.option value="">Toutes catégories</flux:select.option>
            @foreach($categories as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterStatus" class="sm:w-44">
            <flux:select.option value="">Tous statuts</flux:select.option>
            <flux:select.option value="pending">En attente</flux:select.option>
            <flux:select.option value="approved">Approuvée</flux:select.option>
            <flux:select.option value="rejected">Rejetée</flux:select.option>
        </flux:select>
    </flux:card>

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-slate-500 uppercase tracking-wider bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700">
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-left">Catégorie</th>
                    <th class="px-5 py-3 text-left">Description</th>
                    <th class="px-5 py-3 text-left">Compte</th>
                    <th class="px-5 py-3 text-right">Montant</th>
                    <th class="px-5 py-3 text-center">Statut</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($expenses as $expense)
                    @php
                        $statusStyles = match($expense->status) {
                            'approved' => ['bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400', 'Approuvée'],
                            'pending' => ['bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400', 'En attente'],
                            'rejected' => ['bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400', 'Rejetée'],
                            default => ['bg-slate-100 text-slate-600', '—'],
                        };
                        $catLabel = $categories[$expense->category] ?? $expense->category;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $expense->expense_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                {{ $catLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-900 dark:text-white">{{ $expense->description }}</td>
                        <td class="px-5 py-3 text-xs font-mono text-violet-600 dark:text-violet-400">
                            {{ $expense->account?->code ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-red-600">
                            {{ number_format($expense->amount, 0, ',', '.') }} FC
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyles[0] }}">{{ $statusStyles[1] }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if($expense->status === 'pending')
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="approve('{{ $expense->id }}')" wire:confirm="Approuver cette dépense ?"
                                            class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 hover:bg-emerald-200 transition-colors" title="Approuver">
                                        <x-heroicon-o-check class="w-3.5 h-3.5"/>
                                    </button>
                                    <button wire:click="reject('{{ $expense->id }}')" wire:confirm="Rejeter cette dépense ?"
                                            class="p-1.5 rounded-lg bg-red-100 dark:bg-red-500/20 text-red-600 hover:bg-red-200 transition-colors" title="Rejeter">
                                        <x-heroicon-o-x-mark class="w-3.5 h-3.5"/>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <flux:pagination :paginator="$expenses" scroll-to />

    {{-- Create Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nouvelle dépense</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Catégorie *</label>
                        <select wire:model="category" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm">
                            <option value="">Choisir...</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Date *</label>
                        <input wire:model="expense_date" type="date" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm">
                        @error('expense_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Description *</label>
                    <input wire:model="description" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm" placeholder="Description de la dépense">
                    @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Montant (FC) *</label>
                        <input wire:model="amount" type="number" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm" placeholder="0">
                        @error('amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Compte de charge</label>
                        <select wire:model="account_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm">
                            <option value="">Aucun</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm" placeholder="Notes additionnelles..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-600 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Annuler</button>
                <button wire:click="save" class="px-4 py-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 text-white text-sm font-bold hover:from-red-700 hover:to-rose-700 transition-all">Enregistrer</button>
            </div>
        </div>
    </div>
    @endif
</div>
