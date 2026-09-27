<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Gestion des Congés</h1>
            <p class="text-slate-500 text-sm mt-1 font-space font-medium">Gérez, planifiez et suivez les absences et soldes de congés des collaborateurs.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="createLeaveRequest" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouvelle Demande
            </button>
        </div>
    </div>

    <!-- TABS BAR -->
    <div class="flex gap-4 border-b border-slate-200 dark:border-[#1a1a1a] mb-6">
        <button type="button" 
                wire:click="$set('activeTab', 'gantt')" 
                class="pb-3 text-sm font-bold border-b-2 transition-all px-2 font-space {{ $activeTab === 'gantt' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Planning Gantt
        </button>
        <button type="button" 
                wire:click="$set('activeTab', 'demandes')" 
                class="pb-3 text-sm font-bold border-b-2 transition-all px-2 font-space {{ $activeTab === 'demandes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Demandes de Congé
        </button>
        <button type="button" 
                wire:click="$set('activeTab', 'soldes')" 
                class="pb-3 text-sm font-bold border-b-2 transition-all px-2 font-space {{ $activeTab === 'soldes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Solde des Congés
        </button>
    </div>

    @if($activeTab === 'gantt')
        <!-- CALENDRIER GANTT DE CONGES -->
        <div class="mb-8 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-slate-100 dark:border-[#1a1a1a] pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white font-space">Planning Gantt des Congés</h2>
                    <p class="text-xs text-slate-500 mt-1">Visualisez les périodes d'absence et d'indisponibilité par collaborateur.</p>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#333] px-3 py-1.5 rounded-xl">
                    <button type="button" wire:click="previousGanttMonth" class="p-1 hover:bg-slate-200 dark:hover:bg-[#222] rounded-lg transition-colors text-slate-600 dark:text-slate-400">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200 font-space select-none capitalize">
                        {{ Carbon\Carbon::create($ganttYear, $ganttMonth, 1)->translatedFormat('F Y') }}
                    </span>
                    <button type="button" wire:click="nextGanttMonth" class="p-1 hover:bg-slate-200 dark:hover:bg-[#222] rounded-lg transition-colors text-slate-600 dark:text-slate-400">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-[800px] space-y-2">
                    <!-- Day Headers -->
                    <div class="grid grid-cols-12 items-center text-center font-bold text-[10px] text-slate-400 border-b border-slate-100 dark:border-[#1a1a1a] pb-2">
                        <div class="col-span-3 text-left pl-2">Collaborateur</div>
                        <div class="col-span-9 grid gap-px text-center" style="display: grid; grid-template-columns: repeat(31, minmax(0, 1fr));">
                            @foreach($ganttDays as $day)
                                <div class="py-1 rounded border-l border-slate-100 dark:border-[#1a1a1a]/50 select-none">
                                    {{ $day }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Gantt Rows -->
                    @forelse($ganttData as $gRow)
                        <div class="grid grid-cols-12 items-center py-2 border-b border-slate-100 dark:border-[#1a1a1a]/40 hover:bg-slate-50/50 dark:hover:bg-[#111]/10 rounded-lg">
                            <!-- Personnel Title -->
                            <div class="col-span-3 pl-2 flex flex-col">
                                <span class="text-xs font-bold text-slate-900 dark:text-white leading-tight font-space">{{ $gRow['teacher_name'] }}</span>
                                <span class="text-[9px] text-slate-400 mt-0.5">{{ $gRow['specialty'] }}</span>
                            </div>
                            
                            <!-- Timeline Matrix -->
                            <div class="col-span-9 grid gap-px text-center h-5 relative" style="display: grid; grid-template-columns: repeat(31, minmax(0, 1fr));">
                                @foreach($ganttDays as $day)
                                    @php
                                        $isLeave = $gRow['days'][$day] ?? false;
                                        $statusColor = 'bg-transparent';
                                        if ($isLeave) {
                                            if ($gRow['status'] === 'En attente') {
                                                $statusColor = 'bg-yellow-400/60 dark:bg-yellow-500/50 border border-dashed border-yellow-500/40 shadow-sm hover:opacity-85 cursor-help';
                                            } elseif ($gRow['status'] === 'Refusé') {
                                                $statusColor = 'bg-slate-300 dark:bg-slate-700/80 line-through opacity-50 hover:opacity-85 cursor-help';
                                            } else {
                                                // Approuvé - colorer par type
                                                $type = $gRow['type'] ?? 'Congé payé légal';
                                                if ($type === 'RTT') {
                                                    $statusColor = 'bg-purple-500/90 dark:bg-purple-600/80 shadow-sm shadow-purple-500/10 hover:opacity-85 cursor-help';
                                                } elseif ($type === 'Arrêt maladie') {
                                                    $statusColor = 'bg-rose-500/90 dark:bg-rose-600/80 shadow-sm shadow-rose-500/10 hover:opacity-85 cursor-help';
                                                } elseif ($type === 'Congé sans solde') {
                                                    $statusColor = 'bg-amber-500/90 dark:bg-amber-600/80 shadow-sm shadow-amber-500/10 hover:opacity-85 cursor-help';
                                                } else {
                                                    $statusColor = 'bg-emerald-500/90 dark:bg-emerald-600/80 shadow-sm shadow-emerald-500/10 hover:opacity-85 cursor-help';
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="h-full rounded-sm flex items-center justify-center transition-all relative group {{ $statusColor }}"
                                         @if($isLeave)
                                         title="{{ $gRow['type'] ?? 'Congé' }} ({{ $gRow['status'] }}) : du {{ $gRow['start_date'] }} au {{ $gRow['end_date'] }}&#10;Motif : {{ $gRow['reason'] }}@if($gRow['interim_name'])&#10;Intérim : {{ $gRow['interim_name'] }}@endif"
                                         @endif>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-400">Aucun congé enregistré pour ce mois.</div>
                    @endforelse
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-[#1a1a1a] text-[10px] text-slate-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> Congé Payé Légaux</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-purple-500"></span> RTT</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-rose-500"></span> Arrêt Maladie</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> Congé Sans Solde</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-dashed border-yellow-500 bg-yellow-400/60"></span> Demande En attente</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-slate-300 dark:bg-slate-700"></span> Refusé</span>
            </div>
        </div>
    @elseif($activeTab === 'demandes')
        <!-- MAIN CONTENT AREA -->
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
            <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a] flex flex-wrap gap-4 items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-500 uppercase">Filtrer par statut:</span>
                    <select wire:model.live="filter_leave_status" class="py-1 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <option value="all">Tous</option>
                        <option value="En attente">En attente</option>
                        <option value="Approuvé">Approuvés</option>
                        <option value="Refusé">Refusés</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto flex-1 relative">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                        <tr>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Collaborateur</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Type</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Période d'absence</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Motif de l'absence</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Statut</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                        @forelse($leaveRequests as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white font-space">
                                        {{ $item['teacher']['first_name'] ?? '' }} {{ $item['teacher']['last_name'] ?? 'Inconnu' }}
                                    </div>
                                    @if(!empty($item['interim_teacher']))
                                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold font-sans mt-0.5 flex items-center gap-1">
                                            <i data-lucide="user-check" class="w-3 h-3 text-indigo-500"></i>
                                            Intérim : {{ $item['interim_teacher']['first_name'] }} {{ $item['interim_teacher']['last_name'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $type = $item['type'] ?? 'Congé payé légal';
                                        $typeColor = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                                        if ($type === 'RTT') {
                                            $typeColor = 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
                                        } elseif ($type === 'Arrêt maladie') {
                                            $typeColor = 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400';
                                        } elseif ($type === 'Congé sans solde') {
                                            $typeColor = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                        }
                                    @endphp
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-bold {{ $typeColor }}">
                                        {{ $type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                    Du {{ \Carbon\Carbon::parse($item['start_date'])->format('d/m/Y') }}<br>
                                    Au {{ \Carbon\Carbon::parse($item['end_date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                    {{ $item['reason'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($item['status'] === 'Approuvé')
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Approuvé</span>
                                    @elseif($item['status'] === 'En attente')
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">En attente</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">Refusé</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($item['status'] === 'En attente')
                                            <button type="button" wire:click="startApproveLeave({{ $item['id'] }})" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-955/30 dark:text-emerald-400 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                                                Approuver
                                            </button>
                                            <button type="button" wire:click="rejectLeave({{ $item['id'] }})" class="px-2.5 py-1 bg-rose-50 text-rose-700 dark:bg-rose-955/30 dark:text-rose-400 text-xs font-bold rounded-lg hover:bg-rose-100 transition-colors">
                                                Refuser
                                            </button>
                                        @endif
                                        <button type="button" wire:click="editLeaveRequest({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'deleteLeaveRequest', 'Êtes-vous sûr de vouloir supprimer définitivement cette demande de congé ? Cette action est irréversible.')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucune demande de congé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- TAB: SOLDE DES CONGES -->
        <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
            <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a]">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white font-space">Suivi des Soldes de Congés ({{ date('Y') }})</h2>
                <p class="text-xs text-slate-500 mt-1">Détail des droits, jours consommés et jours disponibles par type de congé.</p>
            </div>
            <div class="overflow-x-auto flex-1 relative">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                        <tr>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap">Collaborateur</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap text-center">Congés Payés</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap text-center">RTT</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap text-center">Arrêts Maladie</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap text-center">Congés Sans Solde</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                        @forelse($leaveBalances as $bal)
                            <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors text-xs">
                                <td class="px-4 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white font-space">{{ $bal['teacher_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans">{{ $bal['specialty'] }}</div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-slate-700 dark:text-slate-300">
                                            {{ $bal['cp_used'] }} / {{ $bal['cp_allowance'] }} j.
                                        </span>
                                        <span class="text-[10px] font-semibold mt-0.5 px-2 py-0.5 rounded-full {{ $bal['cp_available'] > 5 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                            {{ $bal['cp_available'] }} j. dispo.
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-slate-700 dark:text-slate-300">
                                            {{ $bal['rtt_used'] }} / {{ $bal['rtt_allowance'] }} j.
                                        </span>
                                        <span class="text-[10px] font-semibold mt-0.5 px-2 py-0.5 rounded-full {{ $bal['rtt_available'] > 2 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                            {{ $bal['rtt_available'] }} j. dispo.
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-full font-bold text-rose-700 bg-rose-100 dark:bg-rose-900/20 dark:text-rose-400">
                                        {{ $bal['maladie_used'] }} jours
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-full font-bold text-amber-700 bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400">
                                        {{ $bal['sans_solde_used'] }} jours
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <button type="button" wire:click="startEditAllowance({{ $bal['teacher_id'] }}, {{ $bal['cp_allowance'] }}, {{ $bal['rtt_allowance'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Modifier les droits annuels">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucun collaborateur actif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- SIDE-OVER: LEAVE REQUEST -->
    <div x-show="$wire.isLeaveOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isLeaveOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isLeaveOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] w-full max-w-md bg-white dark:bg-[#0a0a0a] border-l border-slate-100 dark:border-[#1a1a1a] shadow-2xl flex flex-col"
         x-cloak>
        
        @if($isLeaveOffcanvasOpen)
        <!-- Drawer Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-[#1a1a1a] flex items-center justify-between sticky top-0 bg-white dark:bg-[#0a0a0a] z-10">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 font-space">
                <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                {{ $leave_id ? 'Modifier Demande' : 'Nouvelle Demande de Congé' }}
            </h2>
            <button @click="$wire.isLeaveOffcanvasOpen = false" class="p-1.5 hover:bg-slate-50 dark:hover:bg-[#1a1a1a] rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Drawer Body -->
        <form wire:submit="saveLeaveRequest" class="flex-1 overflow-y-auto flex flex-col justify-between">
            <div class="p-6 space-y-5">
                <div>
                     <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Collaborateur / Employé</label>
                     <select wire:model.live="form_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                         <option value="">Choisir</option>
                         @foreach($personnelList as $p)
                             <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                         @endforeach
                     </select>
                </div>
                <div>
                     <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Type de congé</label>
                     <select wire:model.live="form_leave_type" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                         <option value="Congé payé légal">Congé payé légal</option>
                         <option value="RTT">RTT</option>
                         <option value="Arrêt maladie">Arrêt maladie</option>
                         <option value="Congé sans solde">Congé sans solde</option>
                     </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Du</label>
                        <input type="date" wire:model.live="form_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Au</label>
                        <input type="date" wire:model.live="form_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required>
                    </div>
                </div>

                <!-- Real-time Balance & Alerts Panel -->
                @if($form_teacher_id && $form_start_date && $form_end_date && $estimated_days > 0)
                    <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-2xl border border-slate-200 dark:border-[#222] space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white font-space flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-indigo-500"></i>
                            Détails & Conformité
                        </h4>
                        <div class="grid grid-cols-2 gap-2 text-[11px] font-sans">
                            <div class="p-2 bg-white dark:bg-[#0a0a0a] rounded-xl border border-slate-100 dark:border-[#222]">
                                <span class="text-slate-400 block">Durée calculée</span>
                                <strong class="text-slate-900 dark:text-white font-space text-xs">
                                    {{ $estimated_days }} jours {{ in_array($form_leave_type, ['RTT', 'Congé payé légal']) ? 'ouvrés' : 'calendaires' }}
                                </strong>
                            </div>
                            @if(in_array($form_leave_type, ['RTT', 'Congé payé légal']))
                                <div class="p-2 bg-white dark:bg-[#0a0a0a] rounded-xl border border-slate-100 dark:border-[#222]">
                                    <span class="text-slate-400 block">Solde projeté</span>
                                    <strong class="font-space text-xs {{ $post_balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($estimated_days > $available_balance ? 'text-rose-600 dark:text-rose-400' : 'text-slate-955 dark:text-white') }}">
                                        {{ $post_balance }} / {{ $available_balance }} j.
                                    </strong>
                                </div>
                            @endif
                        </div>

                        <!-- Warnings & Notifications -->
                        @if($overlap_warning)
                            <div class="p-3 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 rounded-xl border border-rose-100 dark:border-rose-950/30 text-[11px] flex gap-2 items-start">
                                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                <span>{{ $overlap_warning }}</span>
                            </div>
                        @endif

                        @if($interim_warning)
                            <div class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 rounded-xl border border-amber-100 dark:border-amber-950/30 text-[11px] flex gap-2 items-start">
                                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                <span>{{ $interim_warning }}</span>
                            </div>
                        @endif

                        @if($type_info_message)
                            <div class="p-3 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 rounded-xl border border-blue-100 dark:border-blue-950/30 text-[11px] flex gap-2 items-start">
                                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                                <span>{{ $type_info_message }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Motif</label>
                    <textarea wire:model="form_reason" rows="3" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white" required></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Désigner l'intérim (Optionnel)</label>
                    <select wire:model.live="form_interim_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
                        <option value="">Aucun intérim désigné</option>
                        @foreach($personnelList as $p)
                            @if($p['id'] != $form_teacher_id)
                                <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Statut Demande</label>
                    <select wire:model="form_leave_status" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
                        <option value="En attente">En attente</option>
                        <option value="Approuvé">Approuvé</option>
                        <option value="Refusé">Refusé</option>
                    </select>
                </div>
            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isLeaveOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer
                </button>
            </div>
        </form>
        @endif
    </div>

    <!-- MODAL: APPROVE & ASSIGN INTERIM -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isApproveModalOpen') }"
         x-show="open"
         x-cloak>
        
        <!-- Backdrop -->
        <div class="absolute inset-0" @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-md bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4"
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
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-500"></i> Valider & Désigner Intérim
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">Sélectionnez le collaborateur actif qui assurera l'intérim / le backup durant la période de ce congé.</p>
                
                @php
                    $approvingLeave = $approvingLeaveId ? \App\Models\LeaveRequest::find($approvingLeaveId) : null;
                @endphp
                @if($approvingLeave)
                    <div class="p-3 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a] text-xs text-slate-600 dark:text-slate-300 space-y-1">
                        <div><strong>Collaborateur :</strong> {{ $approvingLeave->teacher->first_name }} {{ $approvingLeave->teacher->last_name }}</div>
                        <div><strong>Dates :</strong> Du {{ $approvingLeave->start_date->format('d/m/Y') }} au {{ $approvingLeave->end_date->format('d/m/Y') }}</div>
                        <div><strong>Motif :</strong> {{ $approvingLeave->reason }}</div>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Intérimaire Désigné</label>
                    <select wire:model="interim_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="">Aucun backup (Pas d'intérimaire)</option>
                        @foreach($personnelList as $p)
                            @if(!$approvingLeave || $p['id'] != $approvingLeave->teacher_id)
                                <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="confirmApproveLeave" class="flex-1 py-2 text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center justify-center gap-1.5">
                    Confirmer l'Approbation
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: EDIT ALLOWANCE -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isAllowanceModalOpen') }"
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
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-[#1a1a1a]">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 font-space">
                    <i data-lucide="edit-2" class="w-4 h-4 text-indigo-500"></i> Modifier les droits annuels
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Congés Payés Légaux (jours/an)</label>
                    <input type="number" wire:model="editingAllowanceValue" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white" min="0" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">RTT (jours/an)</label>
                    <input type="number" wire:model="editingRttAllowanceValue" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white" min="0" required>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="saveAllowance" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">
                    Enregistrer
                </button>
            </div>
        </div>
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
</div>
