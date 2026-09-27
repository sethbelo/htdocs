<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ 
    selected: [],
    isOffcanvasOpen: @entangle('isOffcanvasOpen')
}">

    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Admissions</h1>
            <p class="text-slate-500 text-sm mt-1">Gérez le flux d'admissions des candidats, l'évaluation des dossiers et les convocations aux entretiens.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="exportExcel" class="px-4 py-2 bg-white dark:bg-[#111] text-emerald-700 dark:text-emerald-400 font-medium rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2 text-sm">
                <i data-lucide="sheet" class="w-4 h-4"></i>
                Excel
            </button>
            <button type="button" wire:click="exportPDF" class="px-4 py-2 bg-white dark:bg-[#111] text-rose-700 dark:text-rose-400 font-medium rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2 text-sm">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                PDF
            </button>
            <button type="button" @click="isOffcanvasOpen = true; $wire.createAdmission()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouvelle Demande
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Demandes</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ count($admissions) }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="files" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">En attente</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($admissions)->where('status', 'En attente')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Acceptées</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($admissions)->where('status', 'Accepté')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Rejetées</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($admissions)->where('status', 'Rejeté')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col min-h-[500px]">
        
        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Search & Filters -->
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-72">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Rechercher un candidat..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                </div>
                
                <select wire:model.live="yearFilter" class="block py-1.5 pl-3 pr-8 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <option value="all">Toutes les années</option>
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                </select>

                <select wire:model.live="statusFilter" class="block py-1.5 pl-3 pr-8 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <option value="all">Tous les statuts</option>
                    <option value="En attente">En attente</option>
                    <option value="Entretien prévu">Entretien prévu</option>
                    <option value="Accepté">Accepté</option>
                    <option value="Rejeté">Rejeté</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="flex-1 overflow-auto relative">
            <div wire:loading.delay class="absolute inset-0 bg-white/50 dark:bg-[#0a0a0a]/50 z-10 backdrop-blur-sm flex items-center justify-center">
                <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                    <tr>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider w-4">
                            <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                        </th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Candidat</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Classe Demandée</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date Dépôt</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Score</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                    @forelse($admissions as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors group cursor-pointer" wire:click="viewAdmission({{ $item['id'] }})">
                            <td class="py-3 px-4 text-sm w-4" @click.stop>
                                <input type="checkbox" value="{{ $item['id'] }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a] opacity-0 group-hover:opacity-100 transition-opacity" :class="selected.includes({{ $item['id'] }}) ? 'opacity-100' : ''">
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-sm ring-2 ring-white dark:ring-[#0a0a0a]" style="background: linear-gradient(135deg, #{{ $item['color'] }}, #{{ $item['color'] }}cc);">
                                        {{ $item['initials'] }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $item['name'] }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $item['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-300">{{ $item['requested_class'] }}</td>
                            <td class="py-3 px-4 text-sm text-slate-500 dark:text-slate-400">{{ $item['date'] }}</td>
                            <td class="py-3 px-4 text-sm">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border
                                      @if($item['status'] === 'En attente') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/40
                                      @elseif($item['status'] === 'Entretien prévu') bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/40
                                      @elseif($item['status'] === 'Accepté') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40
                                      @elseif($item['status'] === 'Rejeté') bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/40
                                      @endif">
                                    <span class="w-1.5 h-1.5 rounded-full 
                                          @if($item['status'] === 'En attente') bg-amber-500 animate-pulse
                                          @elseif($item['status'] === 'Entretien prévu') bg-blue-500
                                          @elseif($item['status'] === 'Accepté') bg-emerald-500
                                          @elseif($item['status'] === 'Rejeté') bg-rose-500
                                          @endif"></span>
                                    {{ $item['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $item['score'] >= 80 ? 'bg-emerald-500' : ($item['score'] >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" 
                                             style="width: {{ $item['score'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 w-6">{{ $item['score'] }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    @if($item['status'] !== 'Accepté')
                                    <button wire:click="setStatus({{ $item['id'] }}, 'Accepté')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors" title="Accepter">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                    @if($item['status'] !== 'Rejeté')
                                    <button wire:click="setStatus({{ $item['id'] }}, 'Rejeté')" class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Rejeter">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                    <button @click.stop="isOffcanvasOpen = true; $wire.editAdmission({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Modifier">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button wire:click="deleteAdmission({{ $item['id'] }})" wire:confirm="Êtes-vous sûr de vouloir supprimer cette demande ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Supprimer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                Aucun candidat trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (Mock) -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Affichage de <span class="font-medium text-slate-900 dark:text-white">{{ count($admissions) }}</span> résultats
            </p>
            <div class="flex items-center gap-1">
                <button class="p-1.5 rounded-lg border border-slate-200 dark:border-[#333] text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-[#0a0a0a] disabled:opacity-40 transition-all" disabled>
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white shadow-sm">1</button>
                <button class="p-1.5 rounded-lg border border-slate-200 dark:border-[#333] text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-white dark:hover:bg-[#0a0a0a] transition-all disabled:opacity-40" disabled>
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- SIDE-OVER: Candidat Details & Form -->
    <div x-show="isOffcanvasOpen" 
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" 
         role="dialog" 
         aria-modal="true"
         x-cloak>
        
        <div x-show="isOffcanvasOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
             @click="isOffcanvasOpen = false"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full pl-0">
            <div x-show="isOffcanvasOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-full pointer-events-auto">
                 
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a]">
                    <div class="w-full max-w-4xl mx-auto flex-1 flex flex-col px-4 sm:px-6 lg:px-8 py-6 relative">
                    
                    @if($viewMode && $activeCandidate)
                    <!-- VIEW MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button @click="isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        
                        <div class="flex items-center gap-4 mt-2">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-xl font-bold text-white shadow-lg ring-4 ring-white dark:ring-[#0a0a0a]" 
                                 style="background: linear-gradient(135deg, #{{ $activeCandidate['color'] }}, #{{ $activeCandidate['color'] }}cc);">
                                 {{ $activeCandidate['initials'] }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">{{ $activeCandidate['name'] }}</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $activeCandidate['email'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mt-6">
                            <button type="button" class="flex-1 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="mail" class="w-4 h-4"></i> Contacter
                            </button>
                            <button type="button" wire:click="editAdmission({{ $activeCandidate['id'] }})" class="flex-1 py-2 text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 dark:bg-[#111] dark:text-slate-300 dark:border-[#333] dark:hover:bg-[#1a1a1a] transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <i data-lucide="edit" class="w-4 h-4"></i> Modifier
                            </button>
                        </div>
                    </div>

                    <div class="p-6 sm:px-8 space-y-8">
                        <div>
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Statut de la demande</h3>
                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-[#111] border border-slate-100 dark:border-[#1a1a1a] rounded-xl">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $activeCandidate['status'] === 'Accepté' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $activeCandidate['status'] }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Mise à jour récemment</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Informations générales</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Classe demandée</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeCandidate['requested_class'] }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Téléphone</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeCandidate['phone'] ?? 'Non renseigné' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Date de dépôt</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeCandidate['date'] }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Score d'évaluation</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 bg-slate-100 dark:bg-[#111] rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $activeCandidate['score'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeCandidate['score'] }}/100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- CREATE / EDIT MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button @click="isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white mt-2">
                            {{ $editMode ? 'Modifier la demande' : 'Nouvelle admission' }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {{ $editMode ? 'Mettez à jour les informations du candidat.' : 'Remplissez le formulaire pour ajouter un candidat.' }}
                        </p>
                    </div>

                    <div class="p-6 sm:px-8 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="saveAdmission" class="space-y-5">
                            
                            <!-- Informations générales -->
                            <h3 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider border-b border-slate-100 dark:border-[#1a1a1a] pb-2 mb-4">Infos générales</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prénom</label>
                                    <input type="text" wire:model="first_name" placeholder="Ex: Jean-Paul" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('first_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom</label>
                                    <input type="text" wire:model="last_name" placeholder="Ex: Kabeya" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('last_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de naissance</label>
                                    <input type="date" wire:model="date_of_birth" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Genre</label>
                                    <select wire:model="gender" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                        <option value="">Sélectionnez</option>
                                        <option value="M">Masculin</option>
                                        <option value="F">Féminin</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                    <input type="email" wire:model="email" placeholder="Ex: candidat@example.com" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('email') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                                    <div x-data="{ phone: @entangle('phone') }" x-init="
                                        const iti = window.intlTelInput($refs.phoneInput, {
                                            initialCountry: 'cd',
                                            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js',
                                            autoPlaceholder: 'aggressive'
                                        });
                                        $refs.phoneInput.addEventListener('blur', () => {
                                            phone = iti.getNumber();
                                        });
                                        $watch('phone', value => {
                                            if(value) {
                                                iti.setNumber(value);
                                            }
                                        });
                                    " wire:ignore class="w-full">
                                        <input x-ref="phoneInput" type="tel" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse complète</label>
                                <textarea wire:model="address" rows="2" placeholder="Ex: Avenue de la Paix, Q/Joli Site, C/Ngaliema, Kinshasa" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all"></textarea>
                            </div>

                            <!-- Scolarité -->
                            <h3 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider border-b border-slate-100 dark:border-[#1a1a1a] pb-2 mb-4 mt-6">Scolarité</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Classe demandée</label>
                                    <select wire:model="requested_class" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                        <option value="">Sélectionnez une classe</option>
                                        <option value="6ème">6ème</option>
                                        <option value="5ème">5ème</option>
                                        <option value="4ème">4ème</option>
                                        <option value="3ème">3ème</option>
                                        <option value="Seconde">Seconde</option>
                                        <option value="Première">Première</option>
                                        <option value="Terminale">Terminale</option>
                                    </select>
                                    @error('requested_class') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">École de provenance</label>
                                    <input type="text" wire:model="previous_school" placeholder="Ex: Collège Mwanga" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                </div>
                            </div>

                            <!-- Responsable légal -->
                            <h3 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider border-b border-slate-100 dark:border-[#1a1a1a] pb-2 mb-4 mt-6">Responsable légal</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom complet du parent/tuteur</label>
                                <input type="text" wire:model="parent_name" placeholder="Ex: M. Jean-Paul Kabeya" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email parent</label>
                                    <input type="email" wire:model="parent_email" placeholder="Ex: parent@example.com" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone parent</label>
                                    <div x-data="{ parent_phone: @entangle('parent_phone') }" x-init="
                                        const itiParent = window.intlTelInput($refs.parentPhoneInput, {
                                            initialCountry: 'cd',
                                            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js',
                                            autoPlaceholder: 'aggressive'
                                        });
                                        $refs.parentPhoneInput.addEventListener('blur', () => {
                                            parent_phone = itiParent.getNumber();
                                        });
                                        $watch('parent_phone', value => {
                                            if(value) {
                                                itiParent.setNumber(value);
                                            }
                                        });
                                    " wire:ignore class="w-full">
                                        <input x-ref="parentPhoneInput" type="tel" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    </div>
                                </div>
                            </div>

                            <!-- Décision -->
                            <h3 class="text-xs font-semibold text-indigo-600 uppercase tracking-wider border-b border-slate-100 dark:border-[#1a1a1a] pb-2 mb-4 mt-6">Traitement</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut</label>
                                    <select wire:model="status" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                        <option value="En attente">En attente</option>
                                        <option value="Entretien prévu">Entretien prévu</option>
                                        <option value="Accepté">Accepté</option>
                                        <option value="Rejeté">Rejeté</option>
                                    </select>
                                    @error('status') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Score d'évaluation</label>
                                    <input type="number" wire:model="score" min="0" max="100" placeholder="Ex: 85" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('score') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes médicales / Commentaires</label>
                                <div x-data="{ medical_notes: @entangle('medical_notes') }" x-init="
                                    let quill = new Quill($refs.editor, {
                                        theme: 'snow',
                                        placeholder: 'Indiquez ici les détails médicaux, allergies ou commentaires spécifiques...'
                                    });
                                    quill.root.innerHTML = medical_notes || '';
                                    quill.on('text-change', () => {
                                        medical_notes = quill.root.innerHTML;
                                    });
                                    $watch('medical_notes', value => {
                                        if (value !== quill.root.innerHTML) {
                                            quill.root.innerHTML = value || '';
                                        }
                                    });
                                " wire:ignore class="mt-1">
                                    <div x-ref="editor" class="bg-white dark:bg-[#0a0a0a] min-h-[100px] border border-slate-200 dark:border-[#333] rounded-lg text-slate-900 dark:text-white"></div>
                                </div>
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                                <button type="button" @click="isOffcanvasOpen = false" class="flex-1 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                                    <div wire:loading wire:target="saveAdmission" class="ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- On re-trigger lucide icons after livewire update -->
@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript
