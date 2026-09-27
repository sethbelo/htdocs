<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">

    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Personnel & RH</h1>
            <p class="text-slate-500 text-sm mt-1">Gérez le personnel enseignant, les congés, le suivi des heures (timesheets) et la paie.</p>
        </div>
        <div class="flex gap-3">
            @if($activeTab === 'personnel')
                <button type="button" wire:click="createPersonnel" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    Nouveau collaborateur
                </button>
            @elseif($activeTab === 'conges')
                <button type="button" wire:click="createLeaveRequest" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                    Demande de congé
                </button>
            @elseif($activeTab === 'timesheets')
                <div class="flex gap-2">
                    <button type="button" wire:click="openExportModal" class="px-4 py-2 bg-white dark:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#333] rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-[#222] transition-colors shadow-sm flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i>
                        Exporter PDF
                    </button>
                    <button type="button" wire:click="createTimesheet" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        Enregistrer Heures
                    </button>
                </div>
            @elseif($activeTab === 'paie')
                <button type="button" wire:click="createPayslip" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                    <i data-lucide="file-plus" class="w-4 h-4"></i>
                    Générer Bulletin
                </button>
            @endif
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg text-sm flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- TABS -->
    <div class="flex gap-2 border-b border-slate-200 dark:border-[#333] mb-6">
        <button wire:click="switchTab('personnel')" class="px-4 py-3 font-semibold text-sm border-b-2 transition-colors {{ $activeTab === 'personnel' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
            Annuaire & Recrutement
        </button>
        <button wire:click="switchTab('conges')" class="px-4 py-3 font-semibold text-sm border-b-2 transition-colors {{ $activeTab === 'conges' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
            Gestion des Congés & Absences
        </button>
        <button wire:click="switchTab('timesheets')" class="px-4 py-3 font-semibold text-sm border-b-2 transition-colors {{ $activeTab === 'timesheets' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
            Feuilles de Temps
        </button>
        <button wire:click="switchTab('paie')" class="px-4 py-3 font-semibold text-sm border-b-2 transition-colors {{ $activeTab === 'paie' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
            Rémunération & Bulletins Paie
        </button>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        
        <!-- PERSONNEL TAB -->
        @if($activeTab === 'personnel')
            <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a] flex flex-wrap gap-4 items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-slate-500 uppercase">Filtrer par statut:</span>
                    <select wire:model.live="filter_personnel_status" class="py-1 px-3 border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <option value="all">Tous</option>
                        <option value="Candidat">Candidats</option>
                        <option value="Actif">Actifs</option>
                        <option value="En congé">En congé</option>
                        <option value="Ancien">Anciens / Départs</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto flex-1 relative">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                        <tr>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Collaborateur / Candidat</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Contact Details</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap">Spécialité & Rôle</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Salaire Base</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-center">Statut</th>
                            <th class="px-6 py-4 font-semibold whitespace-nowrap text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                        @forelse($personnelList as $item)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($item['avatar']))
                                            <img src="{{ asset('storage/' . $item['avatar']) }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 dark:border-[#222]">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                                {{ strtoupper(substr($item['first_name'], 0, 1)) }}{{ strtoupper(substr($item['last_name'], 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $item['first_name'] }} {{ $item['last_name'] }}</div>
                                            <div class="text-xs text-slate-400">
                                                @if($item['contract_signed_at'])
                                                    Contrat signé le {{ \Carbon\Carbon::parse($item['contract_signed_at'])->format('d/m/Y') }}
                                                @else
                                                    Pas de contrat
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ $item['email'] ?? 'Non renseigné' }}</span>
                                        <span class="text-xs text-slate-400">{{ $item['phone'] ?? '' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                    {{ $item['specialty'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700 dark:text-slate-300">
                                    {{ $item['salary'] ? number_format($item['salary'], 2, ',', ' ') . ' $' : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($item['status'] === 'Actif')
                                        <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Actif</span>
                                    @elseif($item['status'] === 'Candidat')
                                        <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Candidat</span>
                                    @elseif($item['status'] === 'En congé')
                                        <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">En congé</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Ancien</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($item['status'] === 'Candidat')
                                            <button type="button" wire:click="signContract({{ $item['id'] }})" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 text-xs font-semibold rounded-lg hover:bg-emerald-100 transition-colors">
                                                Signer Contrat
                                            </button>
                                        @elseif($item['status'] === 'Actif')
                                            <button type="button" wire:click="terminateContract({{ $item['id'] }})" wire:confirm="Marquer le départ de ce collaborateur ?" class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 text-xs font-semibold rounded-lg hover:bg-rose-100 transition-colors">
                                                Départ
                                            </button>
                                        @endif
                                        <button type="button" wire:click="editPersonnel({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" wire:click="deletePersonnel({{ $item['id'] }})" wire:confirm="Supprimer ce profil ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucun personnel enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- CONGES TAB -->
        @if($activeTab === 'conges')
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
                                    <div class="font-bold text-slate-900 dark:text-white">
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
                                            <button type="button" wire:click="approveLeave({{ $item['id'] }})" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-955/30 dark:text-emerald-400 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                                                Approuver
                                            </button>
                                            <button type="button" wire:click="rejectLeave({{ $item['id'] }})" class="px-2.5 py-1 bg-rose-50 text-rose-700 dark:bg-rose-955/30 dark:text-rose-400 text-xs font-bold rounded-lg hover:bg-rose-100 transition-colors">
                                                Refuser
                                            </button>
                                        @endif
                                        <button type="button" wire:click="editLeaveRequest({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" wire:click="deleteLeaveRequest({{ $item['id'] }})" wire:confirm="Supprimer ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
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
        @endif

        <!-- TIMESHEETS TAB -->
        @if($activeTab === 'timesheets')
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
                                        <button type="button" wire:click="deleteTimesheet({{ $item['id'] }})" wire:confirm="Supprimer ce relevé ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
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
        @endif

        <!-- PAYSLIPS TAB -->
        @if($activeTab === 'paie')
            <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-[#1a1a1a]">
                
                <!-- Left: Payslips list -->
                <div class="lg:col-span-2 overflow-x-auto">
                    <div class="p-4 bg-slate-50/50 dark:bg-[#111]/30 border-b border-slate-100 dark:border-[#1a1a1a] flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulletins de Paie émis</h3>
                        <button type="button" wire:click="openDetailedPayrollModal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-sm transition-all">
                            <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i> Fiche Détaillée
                        </button>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50/50 dark:bg-[#0c0c0c] border-b border-slate-100 dark:border-[#1a1a1a]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Collaborateur</th>
                                <th class="px-4 py-3 font-semibold">Mois</th>
                                <th class="px-4 py-3 font-semibold text-right">Salaire Net</th>
                                <th class="px-4 py-3 font-semibold text-center">Statut</th>
                                <th class="px-4 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                            @forelse($payslipsList as $ps)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                    <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                                        {{ $ps['teacher']['first_name'] ?? '' }} {{ $ps['teacher']['last_name'] ?? '' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-500">
                                        {{ $ps['month_year'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                                        {{ number_format($ps['net_salary'], 2, ',', ' ') }} $
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($ps['status'] === 'Payé')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">Payé</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">Brouillon</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($ps['status'] === 'Brouillon')
                                                <button type="button" wire:click="markAsPaid({{ $ps['id'] }})" class="px-2 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-all">
                                                    Valider Paiement
                                                </button>
                                            @endif
                                            <button type="button" wire:click="viewPayslip({{ $ps['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Visualiser">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" wire:click="exportSinglePayslip({{ $ps['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Télécharger PDF">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" wire:click="editPayslip({{ $ps['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" wire:click="deletePayslip({{ $ps['id'] }})" wire:confirm="Supprimer ce bulletin ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">Aucun bulletin de paie généré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Right: Payroll Assistant Simulator -->
                <div class="p-6 bg-slate-50/20 dark:bg-[#0c0c0c]/10">
                    <h3 class="text-sm font-bold text-indigo-950 dark:text-indigo-400 flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                        <i data-lucide="calculator" class="w-4 h-4 text-indigo-500"></i> Assistant Paie & Rémunération
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Simuler Collaborateur</label>
                            <select wire:model.live="simulated_teacher_id" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                <option value="">Choisir</option>
                                @foreach($personnelList as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Primes & Bonus ($)</label>
                            <input type="number" step="0.01" wire:model.live="simulated_bonus" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Retenues & Déductions ($)</label>
                            <input type="number" step="0.01" wire:model.live="simulated_deductions" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <div class="mt-6 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 text-center">
                            <span class="text-xs text-indigo-700 dark:text-indigo-400 font-semibold block mb-1">Estimation Salaire Net</span>
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($simulated_net, 2, ',', ' ') }} $</span>
                        </div>
                    </div>
                </div>

            </div>
        @endif

    </div>

    <!-- SIDE-OVER: PERSONNEL -->
    <div x-show="$wire.isPersonnelOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isPersonnelOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isPersonnelOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] max-w-3xl w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isPersonnelOffcanvasOpen)
        <form wire:submit.prevent="savePersonnel" class="w-full h-full flex flex-col" x-data="{ 
            useWebcam: false,
            stream: null,
            startWebcam() {
                this.useWebcam = true;
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(s => {
                        this.stream = s;
                        this.$refs.video.srcObject = s;
                    })
                    .catch(err => {
                        console.error('Camera access error:', err);
                        alert('Impossible d\'accéder à la caméra.');
                        this.useWebcam = false;
                    });
            },
            stopWebcam() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
                this.useWebcam = false;
            },
            capturePhoto() {
                const video = this.$refs.video;
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth || 640;
                canvas.height = video.videoHeight || 480;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg');
                @this.set('avatar_webcam', dataUrl);
                this.stopWebcam();
            }
        }" x-init="$watch('$wire.isPersonnelOffcanvasOpen', val => { if(!val) stopWebcam(); })">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        {{ $personnel_id ? 'Modifier le profil' : 'Nouveau Collaborateur' }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Configurez les informations administratives, contractuelles, et les notes.</p>
                </div>
                <button type="button" @click="$wire.isPersonnelOffcanvasOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-8 bg-white dark:bg-[#0a0a0a]">
                
                <!-- ROW 1: Avatar & Basic Information -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Avatar Panel -->
                    <div class="p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#0c0c0c]/30 flex flex-col items-center justify-center space-y-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Photo de Profil</span>
                        
                        <!-- Preview container -->
                        <div class="relative w-36 h-36 rounded-2xl border-2 border-slate-200 dark:border-[#222] bg-white dark:bg-[#050505] flex items-center justify-center overflow-hidden group shadow-inner">
                            @if($avatar_webcam)
                                <img src="{{ $avatar_webcam }}" class="w-full h-full object-cover">
                            @elseif($avatar_upload)
                                <img src="{{ $avatar_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif($avatar_path)
                                <img src="{{ asset('storage/' . $avatar_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="user" class="w-12 h-12 text-slate-300 dark:text-slate-700"></i>
                                    <span class="text-[10px] mt-1">Pas d'image</span>
                                </div>
                            @endif

                            <!-- Overlay details -->
                            <div class="absolute inset-0 bg-slate-955/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button type="button" @click="$refs.fileInput.click()" class="p-2 bg-white/95 rounded-lg text-slate-800 hover:bg-white shadow" title="Télécharger">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                </button>
                                <button type="button" @click="startWebcam()" class="p-2 bg-white/95 rounded-lg text-slate-800 hover:bg-white shadow" title="Caméra">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden File input -->
                        <input x-ref="fileInput" type="file" wire:model="avatar_upload" class="hidden" accept="image/*">
                        
                        <!-- Webcam streaming layer overlay -->
                        <div x-show="useWebcam" class="fixed inset-0 z-[1000] bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
                            <div class="bg-white dark:bg-[#0a0a0a] rounded-3xl border border-slate-200 dark:border-[#222] p-6 max-w-md w-full flex flex-col space-y-4">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="camera" class="w-4 h-4"></i> Webcam Capture
                                </h3>
                                <div class="relative rounded-2xl overflow-hidden bg-black aspect-video flex items-center justify-center">
                                    <video x-ref="video" autoplay playsinline class="w-full h-full object-cover"></video>
                                </div>
                                <div class="flex gap-3 justify-end">
                                    <button type="button" @click="stopWebcam()" class="px-4 py-2 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-[#222] dark:text-slate-300 rounded-lg hover:bg-slate-200 transition-all">
                                        Annuler
                                    </button>
                                    <button type="button" @click="capturePhoto()" class="px-4 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all">
                                        Prendre la photo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-400 text-center leading-relaxed">
                            Formats acceptés: JPG, PNG.<br>Taille max: 1 Mo.
                        </div>
                    </div>

                    <!-- Right: Basic details -->
                    <div class="lg:col-span-2 p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 space-y-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Informations Personnelles</span>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Prénom</label>
                                <input type="text" wire:model="form_first_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom</label>
                                <input type="text" wire:model="form_last_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Adresse Email</label>
                            <input type="email" wire:model="form_email" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <!-- Phone field with Flags (intl-tel-input) -->
                        <div x-data="{
                            iti: null,
                            phoneValue: @entangle('form_phone')
                        }" x-init="
                            $nextTick(() => {
                                iti = window.intlTelInput($refs.phoneInput, {
                                    initialCountry: 'cd',
                                    preferredCountries: ['cd', 'be', 'fr', 'us'],
                                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js'
                                });
                                if (phoneValue) {
                                    iti.setNumber(phoneValue);
                                }
                                $refs.phoneInput.addEventListener('countrychange', () => {
                                    phoneValue = iti.getNumber();
                                });
                                $refs.phoneInput.addEventListener('blur', () => {
                                    phoneValue = iti.getNumber();
                                });
                                $watch('phoneValue', val => {
                                    if (val !== iti.getNumber()) {
                                        iti.setNumber(val || '');
                                    }
                                });
                            });
                        }" wire:ignore>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Numéro de Téléphone</label>
                            <input x-ref="phoneInput" type="tel" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date de naissance</label>
                            <input type="date" wire:model="form_date_of_birth" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('form_date_of_birth') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                </div>

                <!-- ROW 2: Professional Details / Contract & Quill Editor -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Function & Contract details -->
                    <div class="lg:col-span-1 p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 space-y-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Dossier RH & Contrat</span>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Fonction / Rôle</label>
                            <select wire:model="form_specialty" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                <option value="">Sélectionner une fonction</option>
                                <option value="Direction">Direction</option>
                                <option value="Enseignant">Enseignant</option>
                                <option value="Secrétaire">Secrétaire</option>
                                <option value="Comptable">Comptable</option>
                                <option value="Caissier">Caissier</option>
                                <option value="RH">RH / Ressources Humaines</option>
                                <option value="Surveillant">Surveillant</option>
                                <option value="Bibliothécaire">Bibliothécaire</option>
                                <option value="Staff">Autre / Staff</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Salaire de base ($)</label>
                            <input type="number" step="0.01" wire:model="form_salary" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Statut RH</label>
                            <select wire:model="form_status" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                <option value="Candidat">Candidat</option>
                                <option value="Actif">Actif / Collaborateur</option>
                                <option value="En congé">En congé / Absent</option>
                                <option value="Ancien">Ancien / Départ</option>
                            </select>
                        </div>

                        <!-- Signature & Fin Contrat (Full Width col-12) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Signature Contrat</label>
                            <input type="date" wire:model="form_contract_signed_at" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Fin Contrat</label>
                            <input type="date" wire:model="form_contract_ended_at" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>

                        <!-- Documents Joints / Dossier (CV & Lettre de Motivation) -->
                        <div class="pt-4 border-t border-slate-100 dark:border-[#1a1a1a] space-y-3">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Documents Joints (PDF)</span>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Curriculum Vitae (CV)</label>
                                @if($cv_path)
                                    <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-[#111] rounded-lg border border-slate-200 dark:border-[#222] mb-1">
                                        <a href="{{ asset('storage/' . $cv_path) }}" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Voir le CV actuel
                                        </a>
                                    </div>
                                @endif
                                <input type="file" wire:model="cv_upload" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/40 dark:file:text-indigo-400 hover:file:bg-indigo-100 transition-all" accept="application/pdf">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Lettre de Motivation</label>
                                @if($cover_letter_path)
                                    <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-[#111] rounded-lg border border-slate-200 dark:border-[#222] mb-1">
                                        <a href="{{ asset('storage/' . $cover_letter_path) }}" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Voir la Lettre actuelle
                                        </a>
                                    </div>
                                @endif
                                <input type="file" wire:model="cover_letter_upload" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/40 dark:file:text-indigo-400 hover:file:bg-indigo-100 transition-all" accept="application/pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quill text editor notes -->
                    <div class="lg:col-span-2 p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 flex flex-col justify-between space-y-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Notes de Recrutement & Dossier</span>

                        <div class="flex-1" x-data="{
                            quill: null,
                            content: @entangle('form_recruitment_notes')
                        }" x-init="
                            quill = new Quill($refs.editor, {
                                theme: 'snow',
                                placeholder: 'Écrivez vos notes de recrutement, parcours, diplômes...',
                                modules: {
                                    toolbar: [
                                        ['bold', 'italic', 'underline'],
                                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                        ['clean']
                                    ]
                                }
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
                        }" wire:ignore>
                            <div class="bg-white dark:bg-[#050505] rounded-xl overflow-hidden border border-slate-200 dark:border-[#333] shadow-sm">
                                <div x-ref="editor" class="min-h-[140px] text-sm text-slate-900 dark:text-white"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isPersonnelOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer
                </button>
            </div>

        </form>
        @endif
    </div>

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
         class="fixed inset-y-0 right-0 z-[999] max-w-xl w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isLeaveOffcanvasOpen)
        <form wire:submit.prevent="saveLeaveRequest" class="w-full h-full flex flex-col">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        Demande de congé & Absences
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Gérez les demandes de congés et absences de vos collaborateurs.</p>
                </div>
                <button type="button" @click="$wire.isLeaveOffcanvasOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6 bg-white dark:bg-[#0a0a0a]">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Employé</label>
                    <select wire:model.live="form_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
                        <option value="">Sélectionner</option>
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
                        <input type="date" wire:model.live="form_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Au</label>
                        <input type="date" wire:model.live="form_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-[#111] focus:bg-white dark:focus:bg-[#0a0a0a] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all dark:text-white">
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
                                    <strong class="font-space text-xs {{ $post_balance > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($estimated_days > $available_balance ? 'text-rose-600 dark:text-rose-400' : 'text-slate-950 dark:text-white') }}">
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
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Statut</label>
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

    <!-- SIDE-OVER: PAYSLIP -->
    <div x-show="$wire.isPayslipOffcanvasOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[998] bg-slate-900/50 backdrop-blur-sm" 
         @click="$wire.isPayslipOffcanvasOpen = false" 
         x-cloak></div>

    <div x-show="$wire.isPayslipOffcanvasOpen" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[999] max-w-xl w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
         style="margin-top: 0px !important;" 
         x-cloak>
        @if($isPayslipOffcanvasOpen)
        <form wire:submit.prevent="savePayslip" class="w-full h-full flex flex-col">
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#1a1a1a] px-6 py-6 bg-white dark:bg-[#0a0a0a] sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">
                        Émettre Bulletin de Paie
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Émettez des bulletins de paie et gérez les rémunérations.</p>
                </div>
                <button type="button" @click="$wire.isPayslipOffcanvasOpen = false" class="p-2 rounded-full text-slate-400 hover:text-slate-955 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5 bg-white dark:bg-[#0a0a0a]">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Employé</label>
                    <select wire:model.live="form_ps_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                        <option value="">Sélectionner</option>
                        @foreach($personnelList as $p)
                            <option value="{{ $p['id'] }}">{{ $p['first_name'] }} {{ $p['last_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Période (Mois-Année)</label>
                    <input type="text" placeholder="ex: 07-2026" wire:model.live="form_ps_month_year" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Salaire Base ($)</label>
                    <input type="number" step="0.01" wire:model="form_ps_base_salary" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Primes / Bonus ($)</label>
                        <input type="number" step="0.01" wire:model="form_ps_bonus" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Retenues ($)</label>
                        <input type="number" step="0.01" wire:model="form_ps_deductions" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Statut initial</label>
                    <select wire:model="form_ps_status" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="Brouillon">Brouillon / Attente</option>
                        <option value="Payé">Payé d'office</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date Paiement</label>
                        <input type="date" wire:model="form_ps_payment_date" class="block w-full py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Méthode Paiement</label>
                        <select wire:model="form_ps_payment_method" class="block w-full py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            <option value="Virement">Virement Bancaire</option>
                            <option value="Espèces">Espèces</option>
                            <option value="Chèque">Chèque</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes Bulletin</label>
                    <textarea wire:model="form_ps_notes" rows="2" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                </div>
            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-end gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <button type="button" @click="$wire.isPayslipOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                    Enregistrer Bulletin
                </button>
            </div>

        </form>
        @endif
    </div>

    <!-- PREVIEW OVERLAY: PAYSLIP -->
    @if($viewing_payslip_id)
        @php
            $viewSlip = \App\Models\Payslip::with('teacher')->find($viewing_payslip_id);
        @endphp
        @if($viewSlip)
            <div class="fixed inset-0 z-[9999] overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in" style="margin-top: 0px !important;">
                <div class="relative w-full max-w-2xl bg-white dark:bg-[#0a0a0a] rounded-3xl border border-slate-200 dark:border-[#1a1a1a] shadow-2xl p-8 space-y-6">
                    
                    <!-- Close button -->
                    <button type="button" wire:click="closePayslipView" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>

                    <!-- Letterhead / Header -->
                    <div class="flex items-start justify-between border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/20">
                                YS
                            </div>
                            <div>
                                <h2 class="text-lg font-black font-space text-slate-900 dark:text-white">{{ \App\Models\Setting::get('school_name', 'Yeschool') }}</h2>
                                <p class="text-xs text-slate-500">{{ \App\Models\Setting::get('school_address', '123 Etablissement, RDC') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $viewSlip->status === 'Payé' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                Bulletin {{ $viewSlip->status }}
                            </span>
                            <div class="text-xs text-slate-400 mt-2">Émis le {{ $viewSlip->created_at->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-8 text-sm">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase block mb-1">COLLABORATEUR</span>
                            <div class="font-bold text-slate-900 dark:text-white">{{ $viewSlip->teacher->first_name }} {{ $viewSlip->teacher->last_name }}</div>
                            <div class="text-xs text-slate-500">{{ $viewSlip->teacher->specialty ?? 'Rôle non spécifié' }}</div>
                            <div class="text-xs text-slate-500">{{ $viewSlip->teacher->email }}</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-400 uppercase block mb-1">PÉRIODE DE PAIE</span>
                            <div class="font-bold text-slate-900 dark:text-white">Mois: {{ $viewSlip->month_year }}</div>
                            <div class="text-xs text-slate-500">Mode: {{ $viewSlip->payment_method ?? 'Non défini' }}</div>
                            @if($viewSlip->payment_date)
                                <div class="text-xs text-slate-500">Payé le: {{ \Carbon\Carbon::parse($viewSlip->payment_date)->format('d/m/Y') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Earnings Table -->
                    <div class="border border-slate-200 dark:border-[#222] rounded-2xl overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#222]">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Rubrique</th>
                                    <th class="px-6 py-3 font-semibold text-right">Gains ($)</th>
                                    <th class="px-6 py-3 font-semibold text-right">Retenues ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#111]">
                                <tr>
                                    <td class="px-6 py-3 font-medium text-slate-900 dark:text-white">Salaire de base</td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700 dark:text-slate-300">{{ number_format($viewSlip->base_salary, 2, ',', ' ') }}</td>
                                    <td class="px-6 py-3 text-right text-slate-400">-</td>
                                </tr>
                                @if($viewSlip->bonus > 0)
                                    <tr>
                                        <td class="px-6 py-3 font-medium text-slate-900 dark:text-white">Primes & Bonus exceptionnels</td>
                                        <td class="px-6 py-3 text-right font-bold text-emerald-600">{{ number_format($viewSlip->bonus, 2, ',', ' ') }}</td>
                                        <td class="px-6 py-3 text-right text-slate-400">-</td>
                                    </tr>
                                @endif
                                @if($viewSlip->deductions > 0)
                                    <tr>
                                        <td class="px-6 py-3 font-medium text-slate-900 dark:text-white">Retenues sur salaire / Avances</td>
                                        <td class="px-6 py-3 text-right text-slate-400">-</td>
                                        <td class="px-6 py-3 text-right font-bold text-rose-600">{{ number_format($viewSlip->deductions, 2, ',', ' ') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="bg-indigo-50/50 dark:bg-indigo-950/20 font-bold border-t border-slate-200 dark:border-[#222]">
                                <tr>
                                    <td class="px-6 py-4 text-indigo-950 dark:text-indigo-400">Total Net à Payer</td>
                                    <td colspan="2" class="px-6 py-4 text-right text-lg text-indigo-600 dark:text-indigo-400">
                                        {{ number_format($viewSlip->net_salary, 2, ',', ' ') }} $
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($viewSlip->notes)
                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-2xl text-xs text-slate-500">
                            <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Notes additionnelles:</span>
                            {{ $viewSlip->notes }}
                        </div>
                    @endif

                    <!-- Action buttons -->
                    <div class="flex gap-3 justify-end pt-4 border-t border-slate-100 dark:border-[#1a1a1a]">
                        <button type="button" onclick="window.print()" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all flex items-center gap-2">
                            <i data-lucide="printer" class="w-4 h-4"></i> Imprimer
                        </button>
                        <button type="button" wire:click="closePayslipView" class="px-5 py-2 bg-slate-100 text-slate-700 dark:bg-[#1a1a1a] dark:text-slate-300 font-semibold rounded-xl hover:bg-slate-200 transition-all">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Detailed Payroll Modal -->
    <div x-data="{ open: @entangle('isDetailedPayrollModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;">
        
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
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-indigo-500"></i> Fiche de Paie Périodique
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Collaborateur / Employé</label>
                    <select wire:model="filter_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                        <option value="">Sélectionner un collaborateur</option>
                        @foreach($personnelList as $teacher)
                            <option value="{{ $teacher['id'] }}">{{ $teacher['first_name'] }} {{ $teacher['last_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Début</label>
                        <input type="date" wire:model="payroll_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Fin</label>
                        <input type="date" wire:model="payroll_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="exportDetailedPayrollSheet" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i> Exporter PDF
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

@script
<script>
    Livewire.hook('morph.updated', ({ component, el }) => {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endscript
