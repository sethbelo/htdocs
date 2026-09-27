<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Gestion des Conflits & Incidents</h1>
            <p class="text-slate-500 text-sm mt-1 font-space">Suivez, documentez et résolvez les conflits d'équipe et procédures disciplinaires.</p>
        </div>
        <div>
            <button type="button" wire:click="createConflict" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Déclarer un Incident
            </button>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-xl">
                <i data-lucide="folder" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Incidents</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ count($conflictsList) }}</h3>
            </div>
        </div>

        <!-- Card 2: En cours -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 rounded-xl">
                <i data-lucide="activity" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">En Cours</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                    {{ count(array_filter($conflictsList, fn($c) => $c['status'] === 'En cours')) }}
                </h3>
            </div>
        </div>

        <!-- Card 3: Résolu -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Résolus</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                    {{ count(array_filter($conflictsList, fn($c) => $c['status'] === 'Résolu')) }}
                </h3>
            </div>
        </div>

        <!-- Card 4: Critique -->
        <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-6 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 rounded-xl">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Critiques</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                    {{ count(array_filter($conflictsList, fn($c) => $c['severity'] === 'Critique')) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        <!-- Table Filters -->
        <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a] flex flex-wrap gap-4 items-center justify-between">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase">Statut:</span>
                    <select wire:model.live="filter_status" class="py-1 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <option value="all">Tous</option>
                        <option value="En cours">En cours</option>
                        <option value="En attente">En attente</option>
                        <option value="Résolu">Résolu</option>
                        <option value="Escalé">Escalé</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase">Gravité:</span>
                    <select wire:model.live="filter_severity" class="py-1 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <option value="all">Toutes</option>
                        <option value="Faible">Faible</option>
                        <option value="Moyenne">Moyenne</option>
                        <option value="Critique">Critique</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Grid -->
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                    <tr>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Collaborateur impliqué</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Sujet / Titre</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Date déclaré</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Gravité</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Statut</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Rapporté par</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                    @forelse($conflictsList as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                @if($item['employee'])
                                    {{ $item['employee']['first_name'] }} {{ $item['employee']['last_name'] }}
                                @else
                                    <span class="text-slate-400 font-normal">Général / Non spécifié</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300 max-w-xs truncate" title="{{ $item['description'] }}">
                                {{ $item['title'] }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($item['date_reported'])->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item['severity'] === 'Faible')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">Faible</span>
                                @elseif($item['severity'] === 'Moyenne')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400">Moyenne</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400">Critique</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item['status'] === 'Résolu')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Résolu</span>
                                @elseif($item['status'] === 'En cours')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">En cours</span>
                                @elseif($item['status'] === 'En attente')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400">En attente</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">Escalé</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">
                                {{ $item['reporter']['name'] ?? 'Admin' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editConflict({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Modifier">
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
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucun incident ou conflit enregistré avec ces critères.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIDE-OVER: ADD/EDIT INCIDENT -->
    <div x-show="$wire.isConflictOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isConflictOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isConflictOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] w-full max-w-lg bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
         x-cloak>
        
        <!-- Drawer Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-[#1a1a1a] flex items-center justify-between">
            <h2 class="text-md font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="shield-exclamation" class="w-5 h-5 text-indigo-500"></i>
                {{ $conflict_id ? 'Détails & Résolution de l\'Incident' : 'Déclarer un Nouvel Incident' }}
            </h2>
            <button type="button" @click="$wire.isConflictOffcanvasOpen = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-400"></i>
            </button>
        </div>

        <form wire:submit.prevent="saveConflict" class="flex-1 flex flex-col justify-between overflow-hidden">
            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Titre / Sujet de l'incident</label>
                    <input type="text" wire:model="form_title" class="block w-full py-2 px-3 text-sm border @error('form_title') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Altercation verbal au secrétariat, Non-respect des consignes" required>
                    @error('form_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Collaborateur Impliqué</label>
                        <select wire:model="form_employee_involved_id" class="block w-full py-2 px-3 text-sm border @error('form_employee_involved_id') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                            <option value="">-- Non spécifié / Général --</option>
                            @foreach($personnelList as $p)
                                <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                            @endforeach
                        </select>
                        @error('form_employee_involved_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date de l'incident</label>
                        <input type="date" wire:model="form_date_reported" class="block w-full py-2 px-3 text-sm border @error('form_date_reported') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" required>
                        @error('form_date_reported') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Niveau de Gravité</label>
                        <select wire:model="form_severity" class="block w-full py-2 px-3 text-sm border @error('form_severity') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" required>
                            <option value="Faible">Faible</option>
                            <option value="Moyenne">Moyenne</option>
                            <option value="Critique">Critique</option>
                        </select>
                        @error('form_severity') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Statut du dossier</label>
                        <select wire:model.live="form_status" class="block w-full py-2 px-3 text-sm border @error('form_status') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" required>
                            <option value="En cours">En cours</option>
                            <option value="En attente">En attente</option>
                            <option value="Résolu">Résolu</option>
                            <option value="Escalé">Escalé</option>
                        </select>
                        @error('form_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description des faits</label>
                    <textarea wire:model="form_description" rows="5" class="block w-full py-2 px-3 text-sm border @error('form_description') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Saisir la description des faits, témoignages..." required></textarea>
                    @error('form_description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Conditional Resolution Notes -->
                @if($form_status === 'Résolu')
                    <div class="animate-fade-in p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900 rounded-xl space-y-2">
                        <label class="block text-xs font-bold text-emerald-800 dark:text-emerald-400">Mesures prises & Notes de Résolution</label>
                        <textarea wire:model="form_resolution_notes" rows="4" class="block w-full py-2 px-3 text-sm border border-emerald-300 dark:border-emerald-800 rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white focus:ring-2 focus:ring-emerald-500" placeholder="Décrire la décision, sanctions éventuelles ou accord de médiation..." required></textarea>
                        @error('form_resolution_notes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <!-- Footer Action Buttons -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10">
                <button type="button" @click="$wire.isConflictOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    {{ $conflict_id ? 'Mettre à jour' : 'Enregistrer l\'Incident' }}
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
                <p class="text-slate-500 text-xs mt-2">Êtes-vous sûr de vouloir supprimer définitivement cet incident ? Cette action est irréversible.</p>
            </div>
            <div class="px-6 py-4 bg-slate-50 dark:bg-[#111] flex gap-3 justify-end border-t border-slate-100 dark:border-[#1a1a1a]">
                <button type="button" @click="open = false" class="px-4 py-2 text-xs font-semibold bg-white dark:bg-[#222] border border-slate-200 dark:border-[#333] text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-[#1c1c1c] transition-colors">
                    Annuler
                </button>
                <button type="button" wire:click="deleteConflict" class="px-4 py-2 text-xs font-semibold bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
