<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Gestion des Départements</h1>
            <p class="text-slate-500 text-sm mt-1 font-space">Gérez la structure hiérarchique de l'établissement et nommez les responsables.</p>
        </div>
        <div>
            <button type="button" wire:click="createDepartment" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouveau Département
            </button>
        </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                    <tr>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Nom du Département</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Description</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Responsable (Manager)</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Effectif</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                    @forelse($departmentsList as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                {{ $item['name'] }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-sm truncate" title="{{ $item['description'] }}">
                                {{ $item['description'] ?: 'Aucune description' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                @if($item['manager'])
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-[10px]">
                                            {{ substr($item['manager']['first_name'], 0, 1) }}{{ substr($item['manager']['last_name'], 0, 1) }}
                                        </div>
                                        <span>{{ $item['manager']['first_name'] }} {{ $item['manager']['last_name'] }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs italic">Non désigné</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400">
                                    {{ $item['teachers_count'] }} collaborateurs
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editDepartment({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Modifier">
                                        <i class="w-4 h-4" data-lucide="edit-2"></i>
                                    </button>
                                    <button type="button" wire:click="promptDelete({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Supprimer">
                                        <i class="w-4 h-4" data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">Aucun département enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIDE-OVER: ADD/EDIT DEPARTMENT -->
    <div x-show="$wire.isDepartmentOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isDepartmentOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isDepartmentOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] w-full max-w-md bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
         x-cloak>
        
        <!-- Drawer Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-[#1a1a1a] flex items-center justify-between">
            <h2 class="text-md font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="folder-open" class="w-5 h-5 text-indigo-500"></i>
                {{ $department_id ? 'Modifier le Département' : 'Nouveau Département' }}
            </h2>
            <button type="button" @click="$wire.isDepartmentOffcanvasOpen = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-400"></i>
            </button>
        </div>

        <form wire:submit.prevent="saveDepartment" class="flex-1 flex flex-col justify-between overflow-hidden">
            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom du département</label>
                    <input type="text" wire:model="form_name" class="block w-full py-2 px-3 text-sm border @error('form_name') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Comptabilité, Enseignant..." required>
                    @error('form_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Responsable (Manager)</label>
                    <select wire:model="form_manager_id" class="block w-full py-2 px-3 text-sm border @error('form_manager_id') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                        <option value="">-- Non spécifié --</option>
                        @foreach($personnelList as $p)
                            <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                        @endforeach
                    </select>
                    @error('form_manager_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea wire:model="form_description" rows="4" class="block w-full py-2 px-3 text-sm border @error('form_description') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Saisir une courte description des fonctions du département..."></textarea>
                    @error('form_description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10">
                <button type="button" @click="$wire.isDepartmentOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <!-- CONFIRM DELETE MODAL -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isConfirmDeleteModalOpen') }"
         x-show="open"
         x-cloak>
        <div class="absolute inset-0" @click="open = false"></div>
        <div class="relative w-full max-w-sm bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <h3 class="text-md font-bold text-slate-900 dark:text-white">Confirmer la suppression</h3>
                <p class="text-slate-500 text-xs mt-2">Êtes-vous sûr de vouloir supprimer définitivement ce département ? Les membres rattachés verront leur liaison réinitialisée.</p>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-[#111] flex gap-3 justify-end border-t border-slate-100 dark:border-[#1a1a1a]">
                <button type="button" @click="open = false" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-[#222] border border-slate-200 dark:border-[#333] text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-[#1c1c1c] transition-colors">
                    Annuler
                </button>
                <button type="button" wire:click="deleteDepartment" class="px-4 py-2 text-xs font-semibold bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
