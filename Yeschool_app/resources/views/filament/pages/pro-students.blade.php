<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Annuaire des Élèves</h1>
            <p class="text-slate-500 text-sm mt-1">Gérez le registre des élèves, les inscriptions, les transferts de classe et les informations des parents.</p>
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
                    <a href="{{ route('admin.export-students-excel') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Toutes les classes groupées
                    </a>
                    @if($classFilter && $classFilter !== 'all')
                    <a href="{{ route('admin.export-students-excel', ['class_id' => $classFilter]) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Classe sélectionnée uniquement
                    </a>
                    @endif

                    <!-- EXPORT PDF SECTION -->
                    <div class="px-3.5 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-y border-slate-100 dark:border-[#222] my-1">
                        Format PDF (Impression)
                    </div>
                    <a href="{{ route('admin.export-students-pdf') }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Toutes les classes groupées
                    </a>
                    @if($classFilter && $classFilter !== 'all')
                    <a href="{{ route('admin.export-students-pdf', ['class_id' => $classFilter]) }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Classe sélectionnée uniquement
                    </a>
                    @endif
                </div>
            </div>

            @if(!auth()->user()->hasRole('Enseignant'))
            <button type="button" wire:click="createStudent" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Nouvel Élève
            </button>
            @endif
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Élèves</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ count($students) }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Actifs</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($students)->where('status', 'Active')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Filles</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($students)->where('gender', 'F')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-pink-50 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Garçons</p>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ collect($students)->where('gender', 'M')->count() }}</h3>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <i data-lucide="user" class="w-5 h-5"></i>
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
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Rechercher par nom..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                </div>
                
                <select wire:model.live="classFilter" class="block py-1.5 pl-3 pr-8 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <option value="all">Toutes les classes</option>
                    @foreach($schoolClasses as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
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
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Élève</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID Matricule</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Classe</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sexe</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                    @forelse($students as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors group cursor-pointer" wire:click="viewStudent({{ $item['id'] }})">
                            <td class="py-3 px-4 text-sm" @click.stop>
                                <input type="checkbox" value="{{ $item['id'] }}" x-model="selected" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a] opacity-0 group-hover:opacity-100 transition-opacity" :class="selected.includes({{ $item['id'] }}) ? 'opacity-100' : ''">
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <div class="flex items-center gap-3">
                                    @php
                                        $initials = substr($item['first_name'] ?? 'E', 0, 1) . substr($item['last_name'] ?? 'L', 0, 1);
                                        $bgColor = $item['gender'] === 'M' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300';
                                    @endphp
                                    @if(!empty($item['avatar']))
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item['avatar']) }}" class="w-10 h-10 rounded-full object-cover shadow-sm border border-white dark:border-[#222]">
                                    @else
                                        <div class="w-10 h-10 rounded-full {{ $bgColor }} flex items-center justify-center font-bold shadow-sm border border-white dark:border-[#222]">
                                            {{ strtoupper($initials) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $item['first_name'] }} {{ $item['last_name'] }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($item['date_of_birth'])->format('d M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm font-mono text-slate-500 dark:text-slate-400">
                                {{ $item['matricule'] ?? '#STD-' . str_pad($item['id'], 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-3 px-4 text-sm">
                                @if($item['school_class'])
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        <i data-lucide="layers" class="w-3 h-3"></i> {{ $item['school_class']['name'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs italic">Non assigné</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm">
                                {{ $item['gender'] === 'M' ? 'Garçon' : 'Fille' }}
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border
                                      @if($item['status'] === 'Active') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40
                                      @elseif($item['status'] === 'Graduated') bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/40
                                      @elseif($item['status'] === 'Archived') bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700
                                      @else bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/40
                                      @endif">
                                    {{ $item['status'] === 'Active' ? 'Actif' : ($item['status'] === 'Graduated' ? 'Diplômé' : ($item['status'] === 'Archived' ? 'Archivé' : $item['status'])) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right" @click.stop>
                                @if(!auth()->user()->hasRole('Enseignant'))
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" wire:click="editStudent({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Modifier">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    @if($item['status'] !== 'Archived')
                                        <button type="button" wire:click="initiateTransfer({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-colors" title="Transférer de classe">
                                            <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" wire:click="archiveStudent({{ $item['id'] }})" wire:confirm="Archiver cet élève ? (Un élève inscrit ne peut pas être supprimé, mais il sera archivé)." class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-100 rounded-lg transition-colors" title="Archiver">
                                            <i data-lucide="archive" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                </div>
                                @else
                                <span class="text-xs text-slate-400 italic">Lecture seule</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                Aucun élève trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIDE-OVER: Form -->
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
                    
                    @if($viewMode && $activeStudent)
                    <!-- VIEW MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative text-center">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        
                        @php
                            $initials = substr($activeStudent['first_name'], 0, 1) . substr($activeStudent['last_name'], 0, 1);
                            $bgColor = $activeStudent['gender'] === 'M' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300';
                        @endphp
                        
                        @if(!empty($activeStudent['avatar']))
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($activeStudent['avatar']) }}" class="w-24 h-24 mx-auto rounded-full object-cover mb-4 shadow-sm border-4 border-white dark:border-[#0a0a0a]">
                        @else
                            <div class="w-24 h-24 mx-auto rounded-full {{ $bgColor }} flex items-center justify-center text-3xl font-bold mb-4 shadow-sm border-4 border-white dark:border-[#0a0a0a]">
                                {{ strtoupper($initials) }}
                            </div>
                        @endif
                        
                        <h2 class="text-2xl font-bold font-space text-slate-900 dark:text-white">{{ $activeStudent['first_name'] }} {{ $activeStudent['last_name'] }}</h2>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">#STD-{{ str_pad($activeStudent['id'], 4, '0', STR_PAD_LEFT) }}</p>

                        @if(!auth()->user()->hasRole('Enseignant'))
                        <div class="flex justify-center gap-3 mt-6">
                            <button type="button" wire:click="editStudent({{ $activeStudent['id'] }})" class="py-2 px-4 text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 dark:bg-[#111] dark:text-slate-300 dark:border-[#333] dark:hover:bg-[#1a1a1a] transition-all flex items-center gap-2 font-semibold text-sm">
                                <i data-lucide="edit" class="w-4 h-4"></i> Modifier
                            </button>
                            @if($activeStudent['status'] !== 'Archived')
                                <button type="button" wire:click="initiateTransfer({{ $activeStudent['id'] }})" class="py-2 px-4 text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2 font-semibold text-sm">
                                    <i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transférer
                                </button>
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="p-6 sm:px-8 space-y-6">
                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a]">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Informations Scolaires</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">Classe actuelle</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeStudent['school_class']['name'] ?? 'Non assigné' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">Statut</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeStudent['status'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a]">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Informations Personnelles</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">Date de naissance</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($activeStudent['date_of_birth'])->format('d M Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">Sexe</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeStudent['gender'] === 'M' ? 'Masculin' : 'Féminin' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a]">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">Informations du Parent / Tuteur</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">Nom du Parent</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeStudent['parent_name'] ?? 'Non renseigné' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-slate-500">N° WhatsApp</span>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $activeStudent['parent_phone'] ?? 'Non renseigné' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @else
                    <!-- CREATE / EDIT MODE -->
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-gradient-to-r from-slate-50 to-slate-100/50 dark:from-[#111] dark:to-[#111]/30 relative">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold font-space bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent mt-2">
                            {{ $editMode ? 'Modifier l\'élève' : 'Nouvel élève' }}
                        </h2>
                    </div>

                    <div class="p-6 sm:p-12 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="saveStudent" class="max-w-3xl mx-auto space-y-6">
                            
                            <!-- Avatar upload -->
                            <div x-data="{ isUploading: false, progress: 0 }" 
                                 x-on:livewire-upload-start="isUploading = true" 
                                 x-on:livewire-upload-finish="isUploading = false" 
                                 x-on:livewire-upload-error="isUploading = false" 
                                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                                 class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a] flex items-center gap-4">
                                
                                <div class="relative">
                                    @if($avatar_upload)
                                        <img src="{{ $avatar_upload->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500 shadow-sm">
                                    @elseif($avatar_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($avatar_path) }}" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500 shadow-sm">
                                    @else
                                        <div class="w-16 h-16 rounded-full bg-slate-200 dark:bg-[#222] text-slate-400 dark:text-slate-600 flex items-center justify-center border-2 border-dashed border-slate-300 dark:border-slate-800">
                                            <i data-lucide="camera" class="w-6 h-6"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Photo de l'élève</label>
                                    <input type="file" wire:model="avatar_upload" accept="image/*" class="text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/40 dark:file:text-indigo-400 file:cursor-pointer">
                                    
                                    <!-- Progress Bar -->
                                    <div x-show="isUploading" class="w-full bg-slate-200 dark:bg-[#333] h-1.5 rounded-full overflow-hidden mt-2" x-cloak>
                                        <div class="bg-indigo-600 h-1.5 rounded-full transition-all" :style="`width: ${progress}%`"></div>
                                    </div>
                                    @error('avatar_upload') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Prénom</label>
                                    <input type="text" wire:model="first_name" placeholder="Ex: Jean" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all placeholder:text-slate-400">
                                    @error('first_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Nom de famille</label>
                                    <input type="text" wire:model="last_name" placeholder="Ex: Kabamba" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all placeholder:text-slate-400">
                                    @error('last_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Sexe</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="relative flex items-center justify-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all duration-200 select-none
                                            {{ $gender === 'M' 
                                                ? 'bg-blue-50/50 border-blue-500 text-blue-600 dark:bg-blue-900/10 dark:border-blue-500 dark:text-blue-400 shadow-sm' 
                                                : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-300 dark:hover:bg-[#111]' 
                                            }}">
                                            <input type="radio" name="gender" value="M" wire:model="gender" class="sr-only">
                                            <i data-lucide="user" class="w-4 h-4 text-blue-500"></i>
                                            <span class="text-sm font-semibold">M</span>
                                        </label>
                                        <label class="relative flex items-center justify-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all duration-200 select-none
                                            {{ $gender === 'F' 
                                                ? 'bg-pink-50/50 border-pink-500 text-pink-600 dark:bg-pink-900/10 dark:border-pink-500 dark:text-pink-400 shadow-sm' 
                                                : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-300 dark:hover:bg-[#111]' 
                                            }}">
                                            <input type="radio" name="gender" value="F" wire:model="gender" class="sr-only">
                                            <i data-lucide="user" class="w-4 h-4 text-pink-500"></i>
                                            <span class="text-sm font-semibold">F</span>
                                        </label>
                                    </div>
                                    @error('gender') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date de naissance</label>
                                    <input type="date" wire:model="date_of_birth" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('date_of_birth') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="space-y-2 border border-slate-100 dark:border-[#222] p-3 rounded-xl bg-slate-50/50 dark:bg-[#111]/30">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">N° ID (Matricule)</label>
                                    @if(!$editMode)
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-indigo-600 dark:text-indigo-400 select-none">
                                            <input type="checkbox" wire:model.live="auto_generate_matricule" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                                            Générer automatiquement
                                        </label>
                                    @endif
                                </div>
                                
                                @if(!$auto_generate_matricule || $editMode)
                                    <div>
                                        <input type="text" wire:model="matricule" placeholder="Ex: 120409-12345678-2026" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all placeholder:text-slate-400">
                                    </div>
                                @else
                                    <div class="p-2.5 bg-slate-100/50 dark:bg-[#1a1a1a] rounded-lg text-xs text-slate-500 dark:text-slate-400 font-mono">
                                        {{ $matricule ? $matricule : 'Sera généré : [Code école]-[N° unique]-[Année]' }}
                                    </div>
                                @endif
                                @error('matricule') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Affectation de Classe</label>
                                <div class="relative">
                                    <select wire:model="school_class_id" class="block w-full py-2.5 pl-3.5 pr-10 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none cursor-pointer">
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($schoolClasses as $c)
                                            <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['level'] }})</option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                @error('school_class_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Nom du Parent / Tuteur</label>
                                    <input type="text" wire:model="parent_name" placeholder="Ex: M. Kabamba" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all placeholder:text-slate-400">
                                    @error('parent_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">WhatsApp du Parent (ex: +243...)</label>
                                    <input type="text" wire:model="parent_phone" placeholder="Ex: +243812345678" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all placeholder:text-slate-400">
                                    @error('parent_phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Statut de l'élève</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Active' 
                                            ? 'bg-emerald-50/50 border-emerald-500 text-emerald-700 dark:bg-emerald-900/10 dark:border-emerald-500 dark:text-emerald-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Active" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Actif</span>
                                    </label>
                                    <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Graduated' 
                                            ? 'bg-indigo-50/50 border-indigo-500 text-indigo-700 dark:bg-indigo-900/10 dark:border-indigo-500 dark:text-indigo-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Graduated" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Diplômé</span>
                                    </label>
                                    <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Expelled' 
                                            ? 'bg-rose-50/50 border-rose-500 text-rose-700 dark:bg-rose-900/10 dark:border-rose-500 dark:text-rose-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Expelled" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Renvoyé</span>
                                    </label>
                                    <label class="relative flex flex-col items-center justify-center p-2.5 rounded-xl border cursor-pointer transition-all duration-200 text-center select-none
                                        {{ $status === 'Archived' 
                                            ? 'bg-slate-100 border-slate-400 text-slate-700 dark:bg-slate-800 dark:border-slate-500 dark:text-slate-400 shadow-sm' 
                                            : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600 dark:bg-[#0a0a0a] dark:border-[#222] dark:text-slate-400 dark:hover:bg-[#111]' 
                                        }}">
                                        <input type="radio" name="status" value="Archived" wire:model="status" class="sr-only">
                                        <span class="text-xs font-bold">Archivé</span>
                                    </label>
                                </div>
                                @error('status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                                <button type="button" @click="$wire.isOffcanvasOpen = false" class="flex-1 py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                                    <div wire:loading wire:target="saveStudent" class="ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- 4. TRANSFER MODAL -->
    @if($isTransferModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-[#0a0a0a] rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 dark:border-[#1a1a1a] overflow-hidden">
            
            <div class="p-6 border-b border-slate-100 dark:border-[#1a1a1a] flex justify-between items-center">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="arrow-right-left" class="w-5 h-5 text-emerald-500"></i>
                    Transférer l'Élève
                </h3>
                <button type="button" wire:click="$set('isTransferModalOpen', false)" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                    <p class="text-xs text-emerald-800 dark:text-emerald-400 font-semibold uppercase tracking-wider">Élève à transférer</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $transfer_student_name }}</p>
                    
                    @php
                        $selectedYearName = \App\Models\AcademicYear::find(session('selected_academic_year_id'))?->name ?? 'Actuelle';
                    @endphp
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                        Année Scolaire du topbar : <strong class="text-slate-800 dark:text-slate-200">{{ $selectedYearName }}</strong>
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Classe Actuelle</label>
                        <input type="text" value="{{ $transfer_current_class_name }}" disabled class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-slate-50 dark:bg-[#111] text-slate-500 dark:text-slate-400">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Nouvelle Classe (Classe de transfert)</label>
                        <div class="relative">
                            <select wire:model="transfer_class_id" class="block w-full py-2.5 pl-3.5 pr-10 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none cursor-pointer">
                                <option value="">Sélectionner la classe de destination</option>
                                @foreach($schoolClasses as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['level'] }})</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                        @error('transfer_class_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 flex gap-3">
                <button type="button" wire:click="$set('isTransferModalOpen', false)" class="flex-1 py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="confirmTransfer" class="flex-1 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Confirmer le transfert
                </button>
            </div>

        </div>
    </div>
    @endif
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
