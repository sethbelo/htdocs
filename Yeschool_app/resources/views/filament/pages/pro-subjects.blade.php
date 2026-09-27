<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    
    <!-- Top Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Matières & Programmes de Cours</h1>
            <p class="text-slate-500 text-sm mt-1">Configurez le référentiel des matières et programmez les cours (titulaires, cotation max, coefficient) par classe.</p>
        </div>
        
        <!-- Tab Switches (Premium Pills) -->
        <div class="flex bg-slate-100 dark:bg-[#111] p-1 rounded-lg border border-slate-200 dark:border-[#333]">
            <button wire:click="switchTab('matières')" class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all {{ $activeTab === 'matières' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300' }}">
                Référentiel Matières
            </button>
            <button wire:click="switchTab('programme')" class="px-4 py-1.5 text-xs font-semibold rounded-md transition-all {{ $activeTab === 'programme' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300' }}">
                Programmes de Cours
            </button>
        </div>
    </div>

    <!-- Success Flash Alert -->
    @if(session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-800 dark:text-emerald-400 text-sm font-medium flex items-center justify-between animate-fade-in">
            <span class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                {{ session('success') }}
            </span>
            <button @click="event.target.closest('.animate-fade-in').remove()" class="text-emerald-400 hover:text-emerald-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- TAB 1: MATIÈRES (GENERAL SUBJECTS CRUD) -->
    @if($activeTab === 'matières')
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm p-6 space-y-6">
            
            <!-- Filters & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="relative max-w-md w-full">
                    <input type="text" wire:model.live.debounce.300ms="subjectSearch" placeholder="Rechercher une matière..." class="block w-full py-2 pl-10 pr-3 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white/50 dark:bg-[#030303]/30 focus:ring-2 focus:ring-indigo-500 dark:text-white placeholder-slate-400">
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                </div>
                
                <button type="button" wire:click="openNewSubjectModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-500/20">
                    <i data-lucide="plus" class="w-4 h-4"></i> Nouvelle Matière
                </button>
            </div>

            <!-- Subjects Grid -->
            @if(empty($subjects))
                <div class="text-center py-12 text-slate-400">
                    <i data-lucide="book-open" class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-800"></i>
                    <p class="text-sm">Aucune matière trouvée.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($subjects as $subj)
                        <div class="p-5 rounded-2xl border border-slate-200/60 dark:border-[#1c1c1c] bg-white/50 dark:bg-[#090909]/40 flex flex-col justify-between space-y-4 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all group">
                            <div class="flex items-start justify-between">
                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $subj['color'] }};"></div>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="openEditSubjectModal({{ $subj['id'] }})" class="p-1 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-all" title="Modifier">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button wire:click="deleteSubject({{ $subj['id'] }})" wire:confirm="Voulez-vous vraiment supprimer cette matière ?" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all" title="Supprimer">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $subj['name'] }}</h3>
                                <span class="text-[10px] uppercase font-mono text-slate-400 mt-1 block">ID: #{{ $subj['id'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    @endif

    <!-- TAB 2: PROGRAMME DE COURS (COURSE GRID BY CLASS & TERM) -->
    @if($activeTab === 'programme')
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm p-6 space-y-6">
            
            <!-- Grid Filter Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                
                <!-- Left: Filters selector -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl w-full">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Classe</label>
                        <select wire:model.live="selectedClassId" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#030303] dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="">Sélectionner une classe</option>
                            @foreach($schoolClasses as $cl)
                                <option value="{{ $cl['id'] }}">{{ $cl['name'] }} (Niveau {{ $cl['level'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Période / Trimestre</label>
                        <select wire:model.live="selectedTerm" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#030303] dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <option value="Trimestre 1">Trimestre 1</option>
                            <option value="Trimestre 2">Trimestre 2</option>
                            <option value="Trimestre 3">Trimestre 3</option>
                            <option value="Annuel">Annuel</option>
                        </select>
                    </div>
                </div>

                <!-- Right Actions -->
                <div class="flex items-end gap-2">
                    @if($selectedClassId)
                        <button type="button" wire:click="exportPdf" class="p-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-200 rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center justify-center" title="Exporter le Programme en PDF">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                        </button>
                        <button type="button" wire:click="openNewCourseModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-500/20 w-full lg:w-auto">
                            <i data-lucide="plus" class="w-4 h-4"></i> Programmer un Cours
                        </button>
                    @endif
                </div>

            </div>

            <!-- Course Program Listing Grid -->
            @if(!$selectedClassId)
                <div class="text-center py-16 text-slate-400">
                    <i data-lucide="layers" class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-800"></i>
                    <p class="text-sm">Veuillez sélectionner une classe pour afficher son programme de cours.</p>
                </div>
            @elseif(empty($courses))
                <div class="text-center py-16 text-slate-400">
                    <i data-lucide="calendar-range" class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-800"></i>
                    <p class="text-sm">Aucun cours n'est encore programmé pour cette classe au {{ $selectedTerm }}.</p>
                    <button wire:click="openNewCourseModal" class="mt-4 px-4 py-2 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-lg hover:bg-indigo-100">
                        Ajouter le premier cours
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-[#1a1a1a]">
                                <th class="py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Matière</th>
                                <th class="py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Titulaire de Cours</th>
                                <th class="py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Cotation Max</th>
                                <th class="py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Coefficient</th>
                                <th class="py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/50 dark:divide-[#1a1a1a]/50">
                            @foreach($courses as $crs)
                                <tr class="group hover:bg-slate-50/50 dark:hover:bg-[#070707]/30 transition-colors">
                                    <td class="py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-3.5 h-3.5 rounded-full flex-shrink-0" style="background-color: {{ $crs['subject']['color'] ?? '#4f46e5' }};"></div>
                                            <span class="font-bold text-slate-800 dark:text-white">{{ $crs['subject']['name'] ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        @if($crs['teacher'])
                                            <div class="flex items-center gap-2">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($crs['teacher']['first_name'] . ' ' . $crs['teacher']['last_name']) }}&background=6366f1&color=fff" class="w-6 h-6 rounded-full border border-slate-200 dark:border-[#222]" />
                                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                                    {{ $crs['teacher']['first_name'] }} {{ $crs['teacher']['last_name'] }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
                                                Aucun titulaire
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-[#111] border border-slate-200/50 dark:border-[#222] text-slate-700 dark:text-slate-300 font-mono">
                                            {{ $crs['max_score'] }} pts
                                        </span>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono">
                                            ×{{ $crs['coefficient'] }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button wire:click="openEditCourseModal({{ $crs['id'] }})" class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 transition-all" title="Modifier la programmation">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button wire:click="deleteCourse({{ $crs['id'] }})" wire:confirm="Retirer ce cours du programme de la classe ?" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all" title="Retirer du programme">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    @endif

    <!-- --- SLIDE-OVER MODALS --- -->

    <!-- 1. SUBJECT FORM MODAL -->
    <div x-show="$wire.isSubjectModalOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isSubjectModalOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isSubjectModalOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] max-w-md w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isSubjectModalOpen)
        <form wire:submit.prevent="saveSubject" class="w-full h-full flex flex-col">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        {{ $subjectId ? 'Modifier la Matière' : 'Nouvelle Matière' }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Configurez le nom et la couleur d'affichage dans les plannings.</p>
                </div>
                <button type="button" @click="$wire.isSubjectModalOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-955 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6 bg-white dark:bg-[#0a0a0a]">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom de la Matière</label>
                    <input type="text" wire:model="subjectName" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" placeholder="ex: Physique, Chimie..." required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Couleur de Badge</label>
                    <div class="grid grid-cols-5 gap-3">
                        @foreach(['#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#8b5cf6', '#06b6d4', '#14b8a6', '#6b7280'] as $col)
                            <button type="button" wire:click="$set('subjectColor', '{{ $col }}')" class="h-10 rounded-xl border-2 transition-all flex items-center justify-center animate-hover" style="background-color: {{ $col }}; border-color: {{ $subjectColor === $col ? '#4f46e5' : 'transparent' }}">
                                @if($subjectColor === $col)
                                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isSubjectModalOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer
                </button>
            </div>

        </form>
        @endif
    </div>

    <!-- 2. COURSE ASSIGNMENT MODAL -->
    <div x-show="$wire.isCourseModalOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isCourseModalOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isCourseModalOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] max-w-md w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isCourseModalOpen)
        <form wire:submit.prevent="saveCourse" class="w-full h-full flex flex-col">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        {{ $courseId ? 'Modifier la programmation' : 'Programmer un Cours' }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Attribuez une matière, un titulaire, une cotation, et un coefficient.</p>
                </div>
                <button type="button" @click="$wire.isCourseModalOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-955 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 bg-white dark:bg-[#0a0a0a]">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Matière</label>
                    <select wire:model="courseSubjectId" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                        <option value="">Sélectionner la matière</option>
                        @foreach(\App\Models\Subject::orderBy('name')->get() as $sb)
                            <option value="{{ $sb->id }}">{{ $sb->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titulaire de Cours (Enseignant)</label>
                    <select wire:model="courseTeacherId" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="">Aucun titulaire (Non assigné)</option>
                        @foreach(\App\Models\Teacher::orderBy('last_name')->get() as $t)
                            <option value="{{ $t->id }}">{{ $t->first_name }} {{ $t->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cotation Max (points)</label>
                        <input type="number" step="1" wire:model="courseMaxScore" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Coefficient</label>
                        <input type="number" step="1" wire:model="courseCoefficient" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Trimester / Période</label>
                    <select wire:model="courseTerm" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                        <option value="Trimestre 1">Trimestre 1</option>
                        <option value="Trimestre 2">Trimestre 2</option>
                        <option value="Trimestre 3">Trimestre 3</option>
                        <option value="Annuel">Annuel</option>
                    </select>
                </div>

            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isCourseModalOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer la programmation
                </button>
            </div>

        </form>
        @endif
    </div>

</div>

<!-- Re-initialize Lucide Icons on view update -->
@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript
