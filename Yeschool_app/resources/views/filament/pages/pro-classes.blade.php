<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Classes & Niveaux</h1>
            <p class="text-slate-500 text-sm mt-1">Configurez les classes, niveaux d'études, capacités d'accueil et enseignants titulaires.</p>
        </div>
        <div class="flex gap-3">
            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="px-4 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-200 font-medium rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2 text-sm">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Exporter
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-60"></i>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-64 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-lg shadow-lg z-30 py-1.5" x-cloak style="display: none;">
                    <!-- EXPORT EXCEL SECTION -->
                    <div class="px-3.5 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-[#222] mb-1">
                        Format Excel (.csv)
                    </div>
                    <a href="{{ route('admin.export-classes-excel') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Toutes les classes
                    </a>
                    @if($levelFilter && $levelFilter !== 'all')
                    <a href="{{ route('admin.export-classes-excel', ['level' => $levelFilter]) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Niveau sélectionné uniquement
                    </a>
                    @endif

                    <!-- EXPORT PDF SECTION -->
                    <div class="px-3.5 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-y border-slate-100 dark:border-[#222] my-1">
                        Format PDF (Impression)
                    </div>
                    <a href="{{ route('admin.export-classes-pdf') }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Toutes les classes
                    </a>
                    @if($levelFilter && $levelFilter !== 'all')
                    <a href="{{ route('admin.export-classes-pdf', ['level' => $levelFilter]) }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Niveau sélectionné uniquement
                    </a>
                    @endif
                </div>
            </div>

            @if(!auth()->user()->hasRole('Enseignant'))
            <button type="button" wire:click="createClass" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouvelle Classe
            </button>
            @endif
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Classes</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ count($classes) }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Capacité Totale</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($classes)->sum('capacity') }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Élèves Inscrits</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($classes)->sum('enrolled_students') }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        @php
            $placesDispo = collect($classes)->sum('capacity') - collect($classes)->sum('enrolled_students');
        @endphp
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Places Disponibles</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ max(0, $placesDispo) }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                    <i data-lucide="door-open" class="w-5 h-5"></i>
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
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Rechercher une classe..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                </div>
                
                <select wire:model.live="levelFilter" class="block py-1.5 pl-3 pr-8 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <option value="all">Tous les niveaux</option>
                    <option value="Maternelle">Maternelle</option>
                    <option value="Primaire">Primaire</option>
                    <option value="Collège">Collège</option>
                    <option value="Lycée">Lycée</option>
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
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Classe</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Niveau</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Année Scolaire</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Taux de Remplissage</th>
                        @if(!auth()->user()->hasRole('Enseignant'))
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                    @forelse($classes as $item)
                        @php
                            $fillRate = $item['capacity'] > 0 ? round(($item['enrolled_students'] / $item['capacity']) * 100) : 0;
                            $fillColor = $fillRate >= 100 ? 'bg-rose-500' : ($fillRate >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors group cursor-pointer" wire:click="viewClass({{ $item['id'] }})">
                            <td class="py-3 px-4 text-sm w-4" @click.stop>
                                <input type="checkbox" value="{{ $item['id'] }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a] opacity-0 group-hover:opacity-100 transition-opacity" :class="selected.includes({{ $item['id'] }}) ? 'opacity-100' : ''">
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100 dark:border-indigo-800">
                                        <i data-lucide="book-open" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white text-base">{{ $item['name'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-300">{{ $item['level'] }}</td>
                            <td class="py-3 px-4 text-sm text-slate-500 dark:text-slate-400">{{ $item['academic_year'] }}</td>
                            <td class="py-3 px-4 text-sm">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border
                                      @if($item['status'] === 'Active') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40
                                      @else bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700
                                      @endif">
                                    <span class="w-1.5 h-1.5 rounded-full 
                                          @if($item['status'] === 'Active') bg-emerald-500
                                          @else bg-slate-500
                                          @endif"></span>
                                    {{ $item['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden w-24">
                                        <div class="h-full rounded-full {{ $fillColor }}" style="width: {{ min(100, $fillRate) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 min-w-[3rem]">{{ $item['enrolled_students'] }} / {{ $item['capacity'] }}</span>
                                </div>
                            </td>
                            @if(!auth()->user()->hasRole('Enseignant'))
                            <td class="py-3 px-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editClass({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Modifier">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" wire:click="deleteClass({{ $item['id'] }})" wire:confirm="Êtes-vous sûr de vouloir supprimer cette classe ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                Aucune classe trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIDE-OVER: Class Details & Form -->
    <div x-show="$wire.isOffcanvasOpen" 
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" 
         role="dialog" 
         aria-modal="true"
         x-cloak>
        
        <div x-show="$wire.isOffcanvasOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
             @click="$wire.isOffcanvasOpen = false"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full" :class="$wire.viewMode ? 'pl-10' : ''">
            <div x-show="$wire.isOffcanvasOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen pointer-events-auto transition-all"
                 :class="$wire.viewMode ? 'max-w-md' : 'max-w-full'">
                 
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a]">
                    
                    @if($viewMode && $activeClass)
                    <!-- VIEW MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        
                        <div class="flex items-center gap-4 mt-2">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-200 dark:border-indigo-800">
                                 <i data-lucide="book-open" class="w-8 h-8"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold font-space text-slate-900 dark:text-white">{{ $activeClass['name'] }}</h2>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">{{ $activeClass['level'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mt-6">
                            <a href="{{ \App\Filament\Pages\ProStudents::getUrl(['classFilter' => $activeClass['id']]) }}" class="flex-1 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="users" class="w-4 h-4"></i> Voir élèves
                            </a>
                            @if(!auth()->user()->hasRole('Enseignant'))
                            <button type="button" wire:click="editClass({{ $activeClass['id'] }})" class="flex-1 py-2 text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 dark:bg-[#111] dark:text-slate-300 dark:border-[#333] dark:hover:bg-[#1a1a1a] transition-all flex items-center justify-center gap-2 font-semibold text-sm">
                                <i data-lucide="edit" class="w-4 h-4"></i> Modifier
                            </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-6 sm:px-8 space-y-8">
                        <div>
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Statut & Capacité</h3>
                            
                            @php
                                $fillRate = $activeClass['capacity'] > 0 ? round(($activeClass['enrolled_students'] / $activeClass['capacity']) * 100) : 0;
                                $fillColor = $fillRate >= 100 ? 'bg-rose-500' : ($fillRate >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                            @endphp

                            <div class="p-4 bg-slate-50 dark:bg-[#111] border border-slate-100 dark:border-[#1a1a1a] rounded-xl space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Remplissage</span>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $activeClass['enrolled_students'] }} / {{ $activeClass['capacity'] }} élèves</span>
                                </div>
                                <div class="w-full h-2.5 bg-white dark:bg-[#0a0a0a] rounded-full overflow-hidden border border-slate-200 dark:border-[#333]">
                                    <div class="h-full rounded-full {{ $fillColor }}" style="width: {{ min(100, $fillRate) }}%"></div>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-slate-200 dark:border-[#333]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Statut de la classe</span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border
                                          @if($activeClass['status'] === 'Active') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/40
                                          @else bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700
                                          @endif">
                                        {{ $activeClass['status'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Détails Pédagogiques</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Année Scolaire</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeClass['academic_year'] }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Professeur Principal</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Non assigné</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">Salle attitrée</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Non définie</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- CREATE / EDIT MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white mt-2">
                            {{ $editMode ? 'Modifier la classe' : 'Nouvelle classe' }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {{ $editMode ? 'Mettez à jour les informations de cette classe.' : 'Créez une nouvelle classe en renseignant les champs.' }}
                        </p>
                    </div>

                    <div class="p-6 sm:p-12 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="saveClass" class="max-w-3xl mx-auto space-y-6">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Nom de la classe</label>
                                    <input type="text" wire:model="name" placeholder="ex: 6ème A" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Niveau / Cycle</label>
                                    <div class="relative">
                                        <select wire:model="level" class="block w-full py-2.5 pl-3.5 pr-10 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none cursor-pointer">
                                            <option value="">Sélectionnez un niveau</option>
                                            <option value="Maternelle">Maternelle</option>
                                            <option value="Primaire">Primaire</option>
                                            <option value="Collège">Collège</option>
                                            <option value="Lycée">Lycée</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                        </div>
                                    </div>
                                    @error('level') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Capacité max. d'élèves</label>
                                    <input type="number" wire:model="capacity" min="1" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('capacity') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Élèves actuellement inscrits</label>
                                    <input type="number" wire:model="enrolled_students" min="0" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('enrolled_students') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Professeur Titulaire (Principal)</label>
                                    <div class="relative">
                                        <select wire:model="head_teacher_id" class="block w-full py-2.5 pl-3.5 pr-10 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none cursor-pointer">
                                            <option value="">Aucun enseignant attitré</option>
                                            @foreach($teachers as $t)
                                                <option value="{{ $t['id'] }}">{{ $t['first_name'] }} {{ $t['last_name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                        </div>
                                    </div>
                                    @error('head_teacher_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Année Scolaire</label>
                                    <input type="text" wire:model="academic_year" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('academic_year') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Statut de la classe</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Active' 
                                            ? 'bg-emerald-50/50 border-emerald-500 text-emerald-700 dark:bg-emerald-900/10 dark:border-emerald-500 dark:text-emerald-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Active" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Actif</span>
                                    </label>
                                    <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Inactive' 
                                            ? 'bg-slate-100 border-slate-400 text-slate-700 dark:bg-slate-800 dark:border-slate-500 dark:text-slate-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Inactive" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Inactif</span>
                                    </label>
                                </div>
                                @error('status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                                <button type="button" @click="$wire.isOffcanvasOpen = false" class="flex-1 py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer la classe
                                    <div wire:loading wire:target="saveClass" class="ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
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

@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript
