<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Notes de cours</h1>
            <p class="text-slate-500 text-sm mt-1">
                {{ auth()->user()->hasRole('Enseignant') ? 'Gérez vos fiches de préparation de cours et planifications pédagogiques.' : 'Consultez les fiches de préparation de cours publiées par les enseignants.' }}
            </p>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-4 mb-6 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            @if(auth()->user()->hasRole('Enseignant'))
                <!-- Filter by Publication status -->
                <div class="relative w-full sm:w-48">
                    <select wire:model.live="publishFilter" class="block w-full py-2 pl-3 pr-10 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white dark:bg-slate-900 dark:border-[#333] dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none">
                        <option value="all">Tous les états</option>
                        <option value="published">Publiées uniquement</option>
                        <option value="private">Brouillons uniquement</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>

                <!-- Filter by Archive status -->
                <div class="relative w-full sm:w-48">
                    <select wire:model.live="archiveFilter" class="block w-full py-2 pl-3 pr-10 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white dark:bg-slate-900 dark:border-[#333] dark:text-slate-300 focus:ring-indigo-500 focus:border-indigo-500 transition-all appearance-none">
                        <option value="active">Notes Actives</option>
                        <option value="archived">Notes Archivées</option>
                        <option value="all">Toutes les notes</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            @else
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-[#333] px-3 py-1.5 rounded-lg">
                    Fiches de préparation publiées
                </span>
            @endif
        </div>

        <!-- Search input -->
        <div class="relative w-full sm:w-72">
            <input type="text" wire:model.live="searchQuery" class="block w-full py-2 pl-9 pr-3 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white dark:bg-slate-900 dark:border-[#333] dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Rechercher une note...">
            <div class="absolute left-3 top-2.5 text-slate-400">
                <i data-lucide="search" class="w-4 h-4"></i>
            </div>
        </div>
    </div>

    <!-- NOTES CARD GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($notes as $n)
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group relative overflow-hidden">
                <!-- Archive Ribbon Indicator -->
                @if($n['is_archived'])
                    <div class="absolute top-0 right-0 bg-slate-100 dark:bg-slate-900 border-l border-b border-slate-200 dark:border-[#333] text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-bl-lg text-slate-500">
                        Archivée
                    </div>
                @endif

                <div>
                    <!-- Header with publication badge and date -->
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs text-slate-400 font-medium">
                            {{ \Carbon\Carbon::parse($n['created_at'])->translatedFormat('d M Y') }}
                        </span>
                        
                        <div class="flex gap-1.5">
                            @if($n['is_published'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30">
                                    Publié
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-900/30 dark:text-slate-500 border border-slate-200 dark:border-[#333]">
                                    Privé (Brouillon)
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors font-space leading-snug">
                        {{ $n['title'] }}
                    </h3>

                    <!-- Associated Journal details -->
                    @if(!empty($n['class_journal']))
                        <div class="mt-3 flex items-center gap-2 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                            <span class="bg-indigo-50 dark:bg-indigo-950/30 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-900/30">
                                {{ $n['class_journal']['school_class']['name'] ?? 'N/A' }}
                            </span>
                            <span class="text-slate-400">•</span>
                            <span class="text-slate-500 dark:text-slate-400">
                                {{ $n['class_journal']['subject']['name'] ?? 'N/A' }}
                            </span>
                        </div>
                    @endif

                    <!-- Note snippet -->
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-3 line-clamp-3 leading-relaxed font-mono">
                        {{ strip_tags($n['content']) }}
                    </p>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-[#222] flex flex-col space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">
                            Par: {{ ($n['teacher']['first_name'] ?? '') . ' ' . ($n['teacher']['last_name'] ?? '') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <div class="flex gap-1">
                            @if(auth()->user()->hasRole('Enseignant') && $n['teacher']['email'] === auth()->user()->email)
                                <!-- Toggle publish status -->
                                <button wire:click="togglePublish({{ $n['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="{{ $n['is_published'] ? 'Rendre privé' : 'Publier aux admins' }}">
                                    <i data-lucide="{{ $n['is_published'] ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                                </button>
                                
                                <!-- Edit note -->
                                <button wire:click="editNote({{ $n['id'] }})" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors" title="Modifier">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>

                                <!-- Archive note -->
                                <button wire:click="toggleArchive({{ $n['id'] }})" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors" title="{{ $n['is_archived'] ? 'Restaurer' : 'Archiver' }}">
                                    <i data-lucide="{{ $n['is_archived'] ? 'archive-restore' : 'archive' }}" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </div>

                        <div class="flex gap-1.5">
                            <!-- Export PDF button -->
                            <button wire:click="exportPdf({{ $n['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Exporter en PDF">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </button>

                            <!-- View Detail Button -->
                            <button wire:click="viewNote({{ $n['id'] }})" class="px-3 py-1 bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:border-[#333] hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors">
                                Ouvrir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl py-16 text-center text-slate-400 dark:text-slate-500">
                <div class="flex flex-col items-center gap-2">
                    <i data-lucide="file-text" class="w-10 h-10 text-slate-300 dark:text-slate-700"></i>
                    <h3 class="font-bold text-slate-800 dark:text-slate-200">Aucune note trouvée</h3>
                    <p class="text-xs">Les notes de cours liées au journal de classe s'afficheront ici.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- VIEW NOTE DETAILS DRAWER -->
    <div 
        x-data=" { showView: @entangle('isViewOpen') } " 
        x-show="showView" 
        class="fixed inset-0 z-50 overflow-hidden" 
        x-cloak
        style="display: none;"
    >
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm transition-opacity" @click="showView = false"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div 
                class="w-screen max-w-lg bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
                x-show="showView"
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
                            Détail de la préparation
                        </h2>
                        @if($activeNote && !empty($activeNote['class_journal']))
                            <p class="text-xs text-slate-400 mt-1">
                                Leçon : <span class="font-medium text-slate-600 dark:text-slate-300">{{ $activeNote['class_journal']['title'] }}</span> 
                                ({{ $activeNote['class_journal']['school_class']['name'] ?? 'N/A' }} - {{ $activeNote['class_journal']['subject']['name'] ?? 'N/A' }})
                            </p>
                        @endif
                    </div>
                    <button @click="showView = false" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @if($activeNote)
                        <!-- Plan/Content Box -->
                        <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-[#222] rounded-xl p-5">
                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100 dark:border-[#222]">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white font-space">
                                    {{ $activeNote['title'] }}
                                </h3>
                                <div class="flex gap-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $activeNote['is_published'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-900/30 dark:text-slate-500' }}">
                                        {{ $activeNote['is_published'] ? 'Publiée (Admin)' : 'Privée' }}
                                    </span>
                                    @if($activeNote['is_archived'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400">
                                            Archivée
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Full Text body -->
                            <div class="text-slate-700 dark:text-slate-300 text-sm whitespace-pre-line leading-relaxed font-mono">
                                {!! $activeNote['content'] !!}
                            </div>
                        </div>

                        <!-- Associated lesson stats metadata -->
                        <div class="space-y-3.5">
                            <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Métadonnées</h4>
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div class="bg-slate-50 dark:bg-slate-900/30 p-3 rounded-lg border border-slate-100 dark:border-[#222]">
                                    <p class="text-slate-400">Date de création</p>
                                    <p class="font-semibold text-slate-700 dark:text-slate-300 mt-1">
                                        {{ \Carbon\Carbon::parse($activeNote['created_at'])->translatedFormat('d M Y à H:i') }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-900/30 p-3 rounded-lg border border-slate-100 dark:border-[#222]">
                                    <p class="text-slate-400">Auteur (Enseignant)</p>
                                    <p class="font-semibold text-slate-700 dark:text-slate-300 mt-1">
                                        M. {{ ($activeNote['teacher']['first_name'] ?? '') . ' ' . ($activeNote['teacher']['last_name'] ?? '') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="pt-4 border-t border-slate-200 dark:border-[#222] flex gap-3 justify-end">
                        @if($activeNote && auth()->user()->hasRole('Enseignant') && $activeNote['teacher']['email'] === auth()->user()->email)
                            <button onclick="confirm('Voulez-vous vraiment supprimer cette note de préparation ?') || event.stopImmediatePropagation()" wire:click="deleteNote({{ $activeNote['id'] }})" class="px-4 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/20 dark:border-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-900/30 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 mr-auto">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Supprimer
                            </button>
                            <button wire:click="exportPdf({{ $activeNote['id'] }})" class="px-4 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/20 dark:border-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-900/30 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                PDF
                            </button>
                            <button wire:click="editNote({{ $activeNote['id'] }})" class="px-4 py-2 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-950/20 dark:border-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                Modifier
                            </button>
                        @endif
                        <button type="button" @click="showView = false" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg text-sm font-medium transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT NOTE DETAILS DRAWER -->
    <div 
        x-data=" { showEdit: @entangle('isEditOpen') } " 
        x-show="showEdit" 
        class="fixed inset-0 z-50 overflow-hidden" 
        x-cloak
        style="display: none;"
    >
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm transition-opacity" @click="showEdit = false"></div>
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div 
                class="w-screen max-w-lg bg-white dark:bg-[#0a0a0a] border-l border-slate-200 dark:border-[#333] shadow-2xl flex flex-col"
                x-show="showEdit"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="px-6 py-6 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                    <button type="button" @click="showEdit = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                    
                    <div class="flex items-center gap-4 mt-2">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm border border-indigo-100 dark:border-indigo-900/30">
                            <i data-lucide="pencil" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                                Modifier la Préparation
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mettez à jour le contenu de votre préparation de cours.</p>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="saveNote" class="flex-1 overflow-y-auto p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Titre de la préparation</label>
                        <input type="text" wire:model="prep_title" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                        @error('prep_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Plan et contenu du cours</label>
                        <!-- Quill binding for prep_content -->
                        <div x-data="{ content: @entangle('prep_content') }" x-init="
                            let quill = new Quill($refs.editor, {
                                theme: 'snow',
                                placeholder: 'Entrez le plan détaillé du cours, notes de préparation, exercices...'
                            });
                            quill.root.innerHTML = content || '';
                            quill.on('text-change', () => {
                                content = quill.root.innerHTML;
                            });
                            $watch('content', value => {
                                if (value !== quill.root.innerHTML) {
                                    quill.root.innerHTML = value || '';
                                }
                            });
                        " wire:ignore class="mt-1">
                            <div x-ref="editor" class="bg-white dark:bg-[#0a0a0a] min-h-[250px] border border-slate-200 dark:border-[#222] rounded-xl text-slate-900 dark:text-white"></div>
                        </div>
                        @error('prep_content') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="relative flex items-start py-2">
                        <div class="flex items-center h-5">
                            <input id="edit_is_published" type="checkbox" wire:model="is_published" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-slate-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="edit_is_published" class="font-medium text-slate-700 dark:text-slate-300">Publier aux administrateurs</label>
                            <p class="text-slate-500 text-xs">Rend la fiche de préparation visible par les administrateurs.</p>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-200 dark:border-[#222] flex gap-3 justify-end">
                        <button type="button" @click="showEdit = false" class="px-4 py-2 text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 dark:bg-[#111] dark:text-slate-300 dark:border-[#333] dark:hover:bg-[#1a1a1a] transition-all text-sm font-semibold">
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
