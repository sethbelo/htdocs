<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Années Scolaires</h1>
            <p class="text-slate-500 text-sm mt-1">Configurez les années scolaires, gérez l'état en cours et générez des rapports de scolarité.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="openReport" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 font-semibold rounded-lg transition-colors shadow-sm flex items-center gap-2 border border-indigo-100 dark:border-indigo-900/30 text-sm">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                Rapport
            </button>
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
                    <a href="{{ route('admin.export-years-excel') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Toutes les années
                    </a>
                    @if($statusFilter && $statusFilter !== 'all')
                    <a href="{{ route('admin.export-years-excel', ['status' => $statusFilter]) }}" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                        Statut sélectionné uniquement
                    </a>
                    @endif

                    <!-- EXPORT PDF SECTION -->
                    <div class="px-3.5 py-1.5 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-y border-slate-100 dark:border-[#222] my-1">
                        Format PDF (Impression)
                    </div>
                    <a href="{{ route('admin.export-years-pdf') }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Toutes les années
                    </a>
                    @if($statusFilter && $statusFilter !== 'all')
                    <a href="{{ route('admin.export-years-pdf', ['status' => $statusFilter]) }}" target="_blank" class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i>
                        Statut sélectionné uniquement
                    </a>
                    @endif
                </div>
            </div>
            <button type="button" wire:click="createYear" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                Nouvelle Année
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-lg text-sm flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        <!-- Toolbar / Filters -->
        <div class="p-4 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-72">
                    <input type="text" wire:model.live="searchQuery" placeholder="Rechercher une année..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                </div>
                <select wire:model.live="statusFilter" class="block py-1.5 pl-3 pr-8 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    <option value="all">Tous les statuts</option>
                    <option value="active">Active (Actuelle)</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="flex-1 overflow-auto relative">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                    <tr>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Désignation</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date de début</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date de fin</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Statut Actuel</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                    @forelse($academicYears as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors group">
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-white text-sm">
                                {{ $item['name'] }}
                            </td>
                            <td class="py-3 px-4 text-sm text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($item['start_date'])->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-sm text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($item['end_date'])->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($item['is_active'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> En cours
                                    </span>
                                @else
                                    <button type="button" wire:click="setActiveYear({{ $item['id'] }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-400 dark:border-[#333] dark:hover:bg-[#222] transition-colors">
                                        Rendre active
                                    </button>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editYear({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Modifier">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    @if(!$item['is_active'])
                                    <button type="button" wire:click="deleteYear({{ $item['id'] }})" wire:confirm="Supprimer cette année ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Supprimer">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-[#111] rounded-full flex items-center justify-center text-slate-300 dark:text-slate-600 mb-4 border border-slate-100 dark:border-[#1a1a1a]">
                                        <i data-lucide="calendar" class="w-8 h-8"></i>
                                    </div>
                                    <h3 class="text-slate-900 dark:text-white font-medium mb-1">Aucune année scolaire</h3>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Créez votre première année scolaire pour démarrer.</p>
                                </div>
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

        <div class="fixed inset-y-0 right-0 flex max-w-full">
            <div x-show="$wire.isOffcanvasOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen pointer-events-auto transition-all max-w-full">
                 
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a]">
                    
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button type="button" @click="$wire.isOffcanvasOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white mt-2">
                            {{ $year_id ? 'Modifier l\'année' : 'Nouvelle année' }}
                        </h2>
                    </div>

                    <div class="p-6 sm:p-12 flex-1 overflow-y-auto">
                        <form wire:submit.prevent="saveYear" class="max-w-3xl mx-auto space-y-6">
                            
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Désignation</label>
                                <input type="text" wire:model="form_name" placeholder="ex: 2026-2027" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                @error('form_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date de début</label>
                                    <input type="date" wire:model="form_start_date" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('form_start_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Date de fin</label>
                                    <input type="date" wire:model="form_end_date" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#222] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('form_end_date') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 border border-slate-200 dark:border-[#333] rounded-xl bg-slate-50 dark:bg-[#111]">
                                <input type="checkbox" wire:model="form_is_active" id="isActiveCheck" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                <label for="isActiveCheck" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Définir comme année scolaire active (actuelle)</label>
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                                <button type="button" @click="$wire.isOffcanvasOpen = false" class="flex-1 py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Enregistrer l'Année Scolaire
                                    <div wire:loading wire:target="saveYear" class="ml-2 w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </button>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORT MODAL -->
    <div x-show="$wire.isReportModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300"
         @click.self="$wire.isReportModalOpen = false">
        <div class="bg-white dark:bg-[#0a0a0a] rounded-2xl w-full max-w-lg shadow-2xl border border-slate-200 dark:border-[#1a1a1a] overflow-hidden animate-fade-in">
            
            <div class="p-6 border-b border-slate-100 dark:border-[#1a1a1a] flex justify-between items-center">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="text-indigo-500 w-5 h-5"></i>
                    Rapport d'Inscriptions par Année Scolaire
                </h3>
                <button type="button" @click="$wire.isReportModalOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                <p class="text-sm text-slate-500 dark:text-slate-400">Ce graphique représente le volume total d'élèves inscrits et réinscrits au cours de chaque année scolaire configurée.</p>
                
                <div class="space-y-4 pt-2">
                    @foreach($chartData as $data)
                        @php
                            $maxVal = collect($chartData)->max('students_count') ?: 1;
                            $percentage = round(($data['students_count'] / $maxVal) * 100);
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $data['name'] }}</span>
                                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ $data['students_count'] }} Élèves</span>
                            </div>
                            <div class="w-full h-8 bg-slate-100 dark:bg-slate-900 rounded-xl overflow-hidden flex items-center relative border border-slate-200 dark:border-[#1a1a1a]">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-r-lg transition-all duration-500" style="width: {{ max(6, $percentage) }}%"></div>
                                <span class="absolute right-3 text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ $percentage }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 flex justify-end">
                <button type="button" @click="$wire.isReportModalOpen = false" class="py-2.5 px-6 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all text-sm">
                    Fermer
                </button>
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
