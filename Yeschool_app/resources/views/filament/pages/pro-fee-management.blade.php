<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ isModalOpen: @entangle('isModalOpen') }">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Frais Scolaires</h1>
            <p class="text-slate-500 text-sm mt-1">Configurez les types de frais scolaires, les devises associées et les taux de change.</p>
        </div>
        @if(!auth()->user()->hasRole('Caissier'))
        <div>
            <button type="button" @click="isModalOpen = true; $wire.openCreateModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouveau Frais
            </button>
        </div>
        @endif
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Content Area like Finances & Comptabilité -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col">
        <!-- Search bar/Filters -->
        <div class="p-4 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30">
            <div class="relative w-72">
                <input type="text" wire:model.live="searchQuery" placeholder="Rechercher un frais..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
            </div>
        </div>

        <!-- Table List -->
        <div class="flex-1 overflow-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                    <tr>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nom du Frais</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Montant</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Devise</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Taux de Change (USD -> CDF)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Valeur Convertie</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Code OHADA</th>
                        @if(!auth()->user()->hasRole('Caissier'))
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                    @forelse($feeTypes as $fee)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                            <td class="py-3 px-4 text-sm font-medium text-slate-900 dark:text-white">{{ $fee['name'] }}</td>
                            <td class="py-3 px-4 text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($fee['amount'], 2) }}</td>
                            <td class="py-3 px-4 text-sm">
                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 dark:bg-[#222] dark:text-slate-300">
                                    {{ $fee['currency'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-slate-500">{{ number_format($fee['exchange_rate'] ?? 2500, 2) }} FC</td>
                            <td class="py-3 px-4 text-sm font-medium text-slate-500">
                                @if($fee['currency'] === 'USD')
                                    <span>{{ number_format($fee['amount'] * ($fee['exchange_rate'] ?? 2500), 2) }} CDF</span>
                                @else
                                    <span>{{ number_format($fee['amount'] / ($fee['exchange_rate'] ?? 2500), 2) }} USD</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-slate-500 font-mono">
                                {{ $fee['ohada_account_code'] ?: '706' }}
                            </td>
                            @if(!auth()->user()->hasRole('Caissier'))
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="isModalOpen = true; $wire.openEditModal({{ $fee['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Modifier">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="delete({{ $fee['id'] }})" wire:confirm="Confirmez-vous la suppression de ce type de frais ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                Aucun type de frais configuré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL FORM (Slide Over) -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 overflow-hidden" x-cloak style="display: none;">
        <!-- Backdrop -->
        <div x-show="isModalOpen" 
             x-transition.opacity 
             class="absolute inset-0 bg-slate-900/20 backdrop-blur-sm transition-opacity" 
             @click="isModalOpen = false"></div>
        
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-0">
            <div x-show="isModalOpen" 
                 x-transition:enter="transform transition ease-in-out duration-300" 
                 x-transition:enter-start="translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transform transition ease-in-out duration-300" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="translate-x-full" 
                 class="w-screen max-w-md pointer-events-auto">
                 
                <div class="flex h-full flex-col bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#333]">
                    
                    <div class="px-6 py-6 border-b border-slate-200 dark:border-[#333] relative">
                        <button @click="isModalOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#222] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2">
                            {{ $editMode ? 'Modifier le Type de Frais' : 'Nouveau Type de Frais' }}
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Configurez les caractéristiques du frais scolaire.</p>
                    </div>

                    <div class="p-6 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="save" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom du frais <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="name" placeholder="Ex: Minerval 1er Trimestre" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                @error('name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant <span class="text-rose-500">*</span></label>
                                    <input type="number" step="0.01" wire:model="amount" placeholder="Ex: 150.00" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    @error('amount') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Devise <span class="text-rose-500">*</span></label>
                                    <select wire:model="currency" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                        <option value="USD">USD ($)</option>
                                        <option value="CDF">CDF (FC)</option>
                                    </select>
                                    @error('currency') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Taux de change (1 $ = ? FC) <span class="text-rose-500">*</span></label>
                                    <input type="number" step="0.01" wire:model="exchange_rate" placeholder="Ex: 2500.00" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    @error('exchange_rate') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Compte OHADA</label>
                                    <input type="text" wire:model="ohada_account_code" placeholder="Ex: 706" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    @error('ohada_account_code') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-200 dark:border-[#333] flex gap-3">
                                <button type="button" @click="isModalOpen = false" class="flex-1 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript
