<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Gestion des Absences</h1>
            <p class="text-slate-500 text-sm mt-1 font-space">Gérez et suivez les relevés d'heures (timesheets), présences et heures supplémentaires du personnel.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="openExportModal" class="px-4 py-2 bg-white dark:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#333] rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-[#222] transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i>
                Exporter PDF
            </button>
            <button type="button" wire:click="createTimesheet" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Enregistrer Relevé
            </button>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a] flex flex-wrap gap-4 items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-500 uppercase">Mois d'activité:</span>
                <input type="month" wire:model.live="filter_ts_month" class="py-1 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
            </div>
        </div>
        <div class="overflow-x-auto flex-1 relative">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                    <tr>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Collaborateur</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap">Date</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Arrivée - Départ</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Pause (Min)</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Heures Sup</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Total heures</th>
                        <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                    @forelse($timesheetsList as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                {{ $item['teacher']['first_name'] ?? '' }} {{ $item['teacher']['last_name'] ?? '' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700 dark:text-slate-300 font-semibold">
                                {{ substr($item['check_in'], 0, 5) }} - {{ $item['check_out'] ? substr($item['check_out'], 0, 5) : 'Présent' }}
                            </td>
                            <td class="px-6 py-4 text-center text-slate-500">
                                {{ $item['break_minutes'] }}
                            </td>
                            <td class="px-6 py-4 text-center text-amber-600 dark:text-amber-400 font-bold">
                                +{{ floatval($item['overtime_hours']) }}h
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">
                                {{ number_format($item['total_hours'], 2, ',', ' ') }}h
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editTimesheet({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'deleteTimesheet', 'Êtes-vous sûr de vouloir supprimer définitivement ce relevé de temps ? Cette action est irréversible.')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucune feuille de temps enregistrée pour ce mois.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIDE-OVER: TIMESHEET -->
    <div x-show="$wire.isTimesheetOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isTimesheetOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isTimesheetOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] max-w-xl w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isTimesheetOffcanvasOpen)
        <form wire:submit.prevent="saveTimesheet" class="w-full h-full flex flex-col">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        Enregistrer Relevé d'Heures
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Enregistrez les heures de travail et les pauses de vos collaborateurs.</p>
                </div>
                <button type="button" @click="$wire.isTimesheetOffcanvasOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 bg-white dark:bg-[#0a0a0a]">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Employé</label>
                    <select wire:model="form_ts_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                        <option value="">Sélectionner</option>
                        @foreach($personnelList as $p)
                            <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                    <input type="date" wire:model="form_ts_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Arrivée (Check In)</label>
                        <input type="time" wire:model="form_ts_check_in" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Départ (Check Out)</label>
                        <input type="time" wire:model="form_ts_check_out" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pause (Minutes)</label>
                        <input type="number" wire:model="form_ts_break_minutes" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Heures Sup (Overtime)</label>
                        <input type="number" step="0.5" wire:model="form_ts_overtime_hours" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes / Activité</label>
                    <textarea wire:model="form_ts_notes" rows="3" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                </div>
            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isTimesheetOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer
                </button>
            </div>

        </form>
        @endif
    </div>

    <!-- CONFIRM DELETE MODAL -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isConfirmDeleteModalOpen') }"
         x-show="open"
         x-cloak>
        
        <!-- Backdrop -->
        <div class="absolute inset-0" @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-sm bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4"
             x-show="open"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Icon & Body -->
            <div class="p-6 text-center space-y-4">
                <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 rounded-full flex items-center justify-center mx-auto">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white font-space">Confirmation de Suppression</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-sans">{{ $confirmDeleteMessage }}</p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="confirmDelete" class="flex-1 py-2 text-xs bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-rose-500/10 flex items-center justify-center gap-1.5">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <!-- Attendance & Absences Export Modal -->
    <div x-data="{ open: @entangle('isExportModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;"
         x-cloak>
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-sm bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4"
             x-show="open"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-[#1a1a1a]">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 font-space">
                    <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i> Exporter Fiche de Présence
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Collaborateur / Enseignant</label>
                    <select wire:model="export_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
                        <option value="">Tous les collaborateurs actifs</option>
                        @foreach($personnelList as $teacher)
                            <option value="{{ $teacher['id'] }}">{{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Du</label>
                        <input type="date" wire:model="export_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Au</label>
                        <input type="date" wire:model="export_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                    </div>
                </div>
                
                <!-- Options checks -->
                <div class="space-y-2 pt-2">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" wire:model="export_include_absences" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Inclure les absences et congés</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" wire:model="export_only_absences" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Afficher uniquement les absences</span>
                    </label>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="exportAttendancePdf" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i> Exporter PDF
                </button>
            </div>
        </div>
    </div>
</div>
