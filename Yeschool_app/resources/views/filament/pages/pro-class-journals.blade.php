<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Journal de classe</h1>
            <p class="text-slate-500 text-sm mt-1">Consignez et suivez le programme d'enseignement journalier par classe et par matière.</p>
        </div>
        <div class="flex gap-3">
            @if(auth()->user()->hasAnyRole(['Super Admin', 'Directeur General', 'Prefet', 'Enseignant', 'Titulaire']))
            <button type="button" wire:click="createJournal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouvelle Leçon
            </button>
            @endif
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-4 mb-6 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <!-- Filter by Class -->
            <div class="relative w-full sm:w-48">
                <select wire:model.live="classFilter" class="block w-full py-2 pl-3 pr-10 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white dark:bg-slate-900 dark:border-[#333] dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none">
                    <option value="all">Toutes les classes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </div>
            </div>
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
            <input type="text" wire:model.live="searchQuery" class="block w-full py-2 pl-9 pr-3 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white dark:bg-slate-900 dark:border-[#333] dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Rechercher une leçon...">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <i data-lucide="search" class="w-4 h-4"></i>
            </div>
        </div>
    </div>

    <!-- JOURNAL LIST -->
    <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-[#222]">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date & Heure</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Classe</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Matière / Enseignant</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sujet de la leçon</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Note de cours</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($journals as $j)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition-colors">
                            <!-- Date & Time -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($j['date'])->translatedFormat('d M Y') }}
                                </div>
                                <div class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">
                                    {{ $j['start_time'] }} - {{ $j['end_time'] }}
                                </div>
                            </td>
                            <!-- Class -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-200 dark:border-[#333]">
                                    {{ $j['school_class']['name'] ?? 'N/A' }}
                                </span>
                            </td>
                            <!-- Subject & Teacher -->
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900 dark:text-white">
                                    {{ $j['subject']['name'] ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    M. {{ ($j['teacher']['first_name'] ?? '') . ' ' . ($j['teacher']['last_name'] ?? '') }}
                                </div>
                            </td>
                            <!-- Subject Lesson -->
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $j['title'] }}</div>
                                <div class="text-xs text-slate-500 truncate max-w-xs mt-1">{{ strip_tags($j['context']) }}</div>
                            </td>
                            <!-- Course Note status -->
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                @if(empty($j['course_note']))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-900/30 dark:text-slate-500 dark:border-[#333]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Aucune
                                    </span>
                                @elseif($j['course_note']['is_published'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Publiée
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Brouillon (Privé)
                                    </span>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Prep Note Action Button -->
                                    <button wire:click="managePrepNote({{ $j['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Note de préparation">
                                        <i data-lucide="file-signature" class="w-4 h-4"></i>
                                    </button>

                                    @if(auth()->user()->hasAnyRole(['Super Admin', 'Directeur General', 'Prefet']) || (auth()->user()->hasRole('Enseignant') && $j['teacher']['email'] === auth()->user()->email))
                                        <!-- Edit -->
                                        <button wire:click="editJournal({{ $j['id'] }})" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors" title="Modifier">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>
                                        <!-- Delete -->
                                        <button onclick="confirm('Êtes-vous sûr de vouloir supprimer cette leçon du journal ?') || event.stopImmediatePropagation()" wire:click="deleteJournal({{ $j['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Supprimer">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="book-open" class="w-8 h-8 text-slate-300 dark:text-slate-700"></i>
                                    Aucune leçon enregistrée
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- JOURNAL FORM DRAWER (OFFCANVAS) -->
    <div 
        x-data="{ show: @entangle('isOffcanvasOpen') }" 
        x-show="show" 
        class="fixed inset-0 z-50 overflow-hidden" 
        x-cloak
        style="display: none;"
    >
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div 
                class="w-screen max-w-md bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
                x-show="show"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="px-6 py-6 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                    <button type="button" @click="show = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                    
                    <div class="flex items-center gap-4 mt-2">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100 dark:border-indigo-900/30">
                            <i data-lucide="book-open" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                                {{ $editMode ? 'Modifier la Leçon' : 'Ajouter une Leçon' }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Consignez les détails du cours dispensé.</p>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="saveJournal" class="flex-1 overflow-y-auto p-6 space-y-6">
                    <!-- Class & Subject Section -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            Pédagogie
                        </h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Classe</label>
                                <div class="relative">
                                    <select wire:model="school_class_id" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none">
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $c)
                                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                @error('school_class_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Matière</label>
                                <div class="relative">
                                    <select wire:model="subject_id" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all appearance-none">
                                        <option value="">Sélectionner une matière</option>
                                        @foreach($subjects as $s)
                                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                @error('subject_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Date & Hours Section -->
                    <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-[#111]">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            Planification
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date de la leçon</label>
                                <input type="date" wire:model="date" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                @error('date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Heure début</label>
                                    <input type="time" wire:model="start_time" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('start_time') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Heure fin</label>
                                    <input type="time" wire:model="end_time" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('end_time') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lesson Details -->
                    <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-[#111]">
                        <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            Contenu
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Sujet de la leçon</label>
                                <input type="text" placeholder="ex: Structure de l'atome..." wire:model="title_lesson" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                @error('title_lesson') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Context / Programme de matière</label>
                                <div x-data="{ context: @entangle('context') }" x-init="
                                    let quill = new Quill($refs.editor, {
                                        theme: 'snow',
                                        placeholder: 'Objectifs pédagogiques, compétences ciblées...'
                                    });
                                    quill.root.innerHTML = context || '';
                                    quill.on('text-change', () => {
                                        context = quill.root.innerHTML;
                                    });
                                    $watch('context', value => {
                                        if (value !== quill.root.innerHTML) {
                                            quill.root.innerHTML = value || '';
                                        }
                                    });
                                " wire:ignore class="mt-1">
                                    <div x-ref="editor" class="bg-white dark:bg-[#0a0a0a] min-h-[120px] border border-slate-200 dark:border-[#222] rounded-xl text-slate-900 dark:text-white"></div>
                                </div>
                                @error('context') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="pt-6 border-t border-slate-200 dark:border-[#222] flex gap-3 justify-end">
                        <button type="button" @click="show = false" class="px-4 py-2 text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 dark:bg-[#111] dark:text-slate-300 dark:border-[#333] dark:hover:bg-[#1a1a1a] transition-all text-sm font-semibold">
                            Annuler
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PREPARATION NOTE DRAWER (OFFCANVAS) -->
    <div 
        x-data="{ showPrep: @entangle('isPrepNoteOpen') }" 
        x-show="showPrep" 
        class="fixed inset-0 z-50 overflow-hidden" 
        x-cloak
        style="display: none;"
    >
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm transition-opacity" @click="showPrep = false"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div 
                class="w-screen max-w-lg bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
                x-show="showPrep"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="px-6 py-5 bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#222] flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white font-space">
                            Note de préparation du cours
                        </h2>
                        @if($activeJournal)
                            <p class="text-xs text-slate-400 mt-1">
                                Lié à : <span class="font-medium">{{ $activeJournal['title'] }}</span> ({{ $activeJournal['school_class']['name'] ?? 'N/A' }})
                            </p>
                        @endif
                    </div>
                    <button @click="showPrep = false" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                @php
                    $isAuthor = $activeJournal && (auth()->user()->hasRole('Enseignant') && $activeJournal['teacher']['email'] === auth()->user()->email);
                    $canEditNote = auth()->user()->hasRole('Enseignant') && ($isAuthor || !auth()->user()->hasAnyRole(['Super Admin', 'Directeur General', 'Prefet']));
                @endphp

                @if($canEditNote)
                    <!-- Editable view for Teachers -->
                    <form wire:submit.prevent="savePrepNote" class="flex-1 overflow-y-auto p-6 space-y-5">
                        <!-- Note Title -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Titre de la préparation</label>
                            <input type="text" wire:model="prep_title" class="w-full text-sm rounded-lg border-slate-200 dark:border-[#333] dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @error('prep_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Note Content -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Plan et contenu du cours</label>
                            <textarea rows="14" placeholder="Entrez le plan détaillé du cours, notes de préparation, exercices..." wire:model="prep_content" class="w-full text-sm rounded-lg border-slate-200 dark:border-[#333] dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 font-mono"></textarea>
                            @error('prep_content') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Publishing toggle -->
                        <div class="relative flex items-start py-2">
                            <div class="flex items-center h-5">
                                <input id="is_published" type="checkbox" wire:model="is_published" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-slate-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_published" class="font-medium text-slate-700 dark:text-slate-300">Publier aux administrateurs</label>
                                <p class="text-slate-500 text-xs">Si coché, cette préparation de cours sera visible par la direction générale et le préfet.</p>
                            </div>
                        </div>

                        <!-- Form actions -->
                        <div class="pt-4 border-t border-slate-200 dark:border-[#222] flex gap-3 justify-end">
                            <button type="button" @click="showPrep = false" class="px-4 py-2 border border-slate-200 dark:border-[#333] text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                Annuler
                            </button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                @else
                    <!-- Read-only view for Admins and non-author teachers -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        @if($activeJournal && !empty($activeJournal['course_note']) && ($activeJournal['course_note']['is_published'] || auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Directeur General')))
                            <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-[#222] rounded-xl p-5">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white font-space">
                                        {{ $activeJournal['course_note']['title'] }}
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $activeJournal['course_note']['is_published'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                        {{ $activeJournal['course_note']['is_published'] ? 'Publiée' : 'Prive' }}
                                    </span>
                                </div>
                                <div class="text-slate-700 dark:text-slate-300 text-sm whitespace-pre-line leading-relaxed font-mono">
                                    {{ $activeJournal['course_note']['content'] }}
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-16 text-center text-slate-400 dark:text-slate-500">
                                <i data-lucide="eye-off" class="w-12 h-12 text-slate-300 dark:text-slate-700 mb-3"></i>
                                <h3 class="font-bold text-slate-800 dark:text-slate-200">Contenu Privé</h3>
                                <p class="text-xs max-w-xs mt-1">L'enseignant n'a pas encore publié cette note de préparation aux administrateurs.</p>
                            </div>
                        @endif
                        <div class="pt-4 border-t border-slate-200 dark:border-[#222] flex justify-end">
                            <button type="button" @click="showPrep = false" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg text-sm font-medium transition-colors">
                                Fermer
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Re-initialize Lucide Icons on Livewire DOM updates
        Livewire.hook('morph.updated', ({ el, component }) => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
</script>
