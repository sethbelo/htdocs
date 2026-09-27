<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Finances & Comptabilité</h1>
            <p class="text-slate-500 text-sm mt-1">Gestion des paiements, salaires et plan comptable OHADA.</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="openPaymentModal('Entrée')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouvel Encaissement
            </button>
            @if(!auth()->user()->hasRole('Caissier'))
            <button wire:click="openPaymentModal('Sortie')" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-medium hover:bg-rose-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="minus" class="w-4 h-4"></i>
                Nouveau Décaissement
            </button>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex space-x-1 bg-slate-100/50 dark:bg-[#1a1a1a] p-1 rounded-xl mb-6 w-max border border-slate-200 dark:border-[#333]">
        <button wire:click="switchTab('scolarite')" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'scolarite' ? 'bg-white dark:bg-[#2a2a2a] text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-[#444]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Frais Scolaires
        </button>
        <button wire:click="switchTab('situation_eleves')" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'situation_eleves' ? 'bg-white dark:bg-[#2a2a2a] text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-[#444]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Situation des Élèves
        </button>
        <button wire:click="switchTab('rapport_caisse')" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'rapport_caisse' ? 'bg-white dark:bg-[#2a2a2a] text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-[#444]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Rapport de Caisse
        </button>
        @if(!auth()->user()->hasRole('Caissier'))
        <button wire:click="switchTab('salaires')" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'salaires' ? 'bg-white dark:bg-[#2a2a2a] text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-[#444]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            Salaires & Dépenses
        </button>
        <button wire:click="switchTab('ohada')" class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'ohada' ? 'bg-white dark:bg-[#2a2a2a] text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-[#444]' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200' }}">
            État Comptable (OHADA)
        </button>
        @endif
    </div>

    <!-- Content -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col">
        @if ($activeTab === 'scolarite')
            <!-- Frais Scolaires / Entrées Table -->
            <div class="flex-1 overflow-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                        <tr>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Référence</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Élève</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type de Frais</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Méthode</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Montant</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Compte OHADA</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                        @forelse($payments as $p)
                            <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                                <td class="py-3 px-4 text-sm text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') }}</td>
                                <td class="py-3 px-4 text-sm text-slate-500 font-mono">{{ $p['reference'] }}</td>
                                <td class="py-3 px-4 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $p['student']['first_name'] ?? 'N/A' }} {{ $p['student']['last_name'] ?? '' }}
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-500">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md text-xs">{{ $p['fee_type']['name'] ?? 'N/A' }}</span>
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-500">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 dark:bg-[#222] dark:text-slate-300 rounded text-xs">{{ $p['payment_method'] ?? 'Cash' }}</span>
                                </td>
                                <td class="py-3 px-4 text-sm font-semibold text-emerald-600">
                                    +${{ number_format($p['amount'], 2) }}
                                    <div class="text-[10px] text-emerald-500 font-normal">
                                        @if(isset($p['fee_type']['exchange_rate']))
                                            {{ number_format($p['amount'] * $p['fee_type']['exchange_rate'], 0, ',', ' ') }} FC
                                        @else
                                            {{ number_format($p['amount'] * 2500, 0, ',', ' ') }} FC
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-500">
                                    <span class="px-2 py-1 bg-slate-100 dark:bg-[#222] rounded text-xs font-mono">{{ $p['ohada_account_code'] ?? 'N/A' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button wire:click="deletePayment({{ $p['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500 text-sm">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="inbox" class="w-8 h-8 mb-2 opacity-50"></i>
                                        Aucune transaction d'encaissement trouvée.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif ($activeTab === 'rapport_caisse')
            <!-- Rapport de Caisse / Encaissements dashboard and details -->
            <div class="flex-1 flex flex-col h-full overflow-hidden p-6 space-y-6">
                <!-- Visual KPI Cards (Jour, Mois, Année) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Today Inflow -->
                    <div class="p-6 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/5 dark:to-teal-500/5 border border-emerald-500/20 dark:border-emerald-500/10 rounded-2xl flex items-center justify-between shadow-sm relative overflow-hidden">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Encaissements Jour</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">${{ number_format($totalRapportJour, 2) }}</div>
                            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalRapportJour * 2500, 0, ',', ' ') }} FC</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                        </div>
                        <span class="absolute top-2 right-2 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </div>

                    <!-- Month Inflow -->
                    <div class="p-6 bg-gradient-to-br from-indigo-500/10 to-blue-500/10 dark:from-indigo-500/5 dark:to-blue-500/5 border border-indigo-500/20 dark:border-indigo-500/10 rounded-2xl flex items-center justify-between shadow-sm">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Encaissements Mois</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">${{ number_format($totalRapportMois, 2) }}</div>
                            <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalRapportMois * 2500, 0, ',', ' ') }} FC</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="calendar-days" class="w-6 h-6"></i>
                        </div>
                    </div>

                    <!-- Year Inflow -->
                    <div class="p-6 bg-gradient-to-br from-violet-500/10 to-purple-500/10 dark:from-violet-500/5 dark:to-purple-500/5 border border-violet-500/20 dark:border-violet-500/10 rounded-2xl flex items-center justify-between shadow-sm">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Encaissements Année</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white">${{ number_format($totalRapportAnnee, 2) }}</div>
                            <div class="text-xs font-bold text-violet-600 dark:text-violet-400">{{ number_format($totalRapportAnnee * 2500, 0, ',', ' ') }} FC</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-violet-500/20 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <!-- Report Toolbar with Date Limits & CSV Export -->
                <div class="p-4 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#222] rounded-2xl flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button wire:click="openRapportFilterModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-indigo-500/10 transition-all">
                            <i data-lucide="filter" class="w-4 h-4"></i> Filtre Limite
                        </button>
                        
                        <div class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                            <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i>
                            @php
                                $dispStart = $rapport_start_date ? \Carbon\Carbon::parse($rapport_start_date)->format('d/m/Y') : 'Début';
                                $dispEnd = $rapport_end_date ? \Carbon\Carbon::parse($rapport_end_date)->format('d/m/Y') : 'Fin';
                            @endphp
                            <span>Période : <strong>{{ $dispStart }}</strong> au <strong>{{ $dispEnd }}</strong></span>
                            @if($rapport_class_id)
                                <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded font-bold text-[10px]">Classe : {{ \App\Models\SchoolClass::find($rapport_class_id)?->name }}</span>
                            @endif
                            @if($rapport_fee_type_id)
                                <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded font-bold text-[10px]">Frais : {{ \App\Models\FeeType::find($rapport_fee_type_id)?->name }}</span>
                            @endif
                            @if($rapport_payment_method)
                                <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded font-bold text-[10px]">{{ $rapport_payment_method }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="exportRapportCsv" class="px-4 py-2 bg-white dark:bg-[#0a0a0a] hover:bg-slate-50 dark:hover:bg-[#111] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#333] rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i> Exporter CSV
                        </button>
                        <button wire:click="exportRapportPdf" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition-all">
                            <i data-lucide="file-text" class="w-4 h-4"></i> Télécharger PDF
                        </button>
                    </div>
                </div>

                <!-- Report Details Table -->
                <div class="flex-1 overflow-auto border border-slate-200 dark:border-[#222] rounded-2xl bg-white dark:bg-[#0a0a0a]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Élève</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Classe</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Frais</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Méthode</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Référence</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#222]">
                            @forelse($rapportPayments as $p)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                    <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                        {{ isset($p['payment_date']) ? \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') : '' }}
                                    </td>
                                    <td class="py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ $p['student']['first_name'] ?? '' }} {{ $p['student']['last_name'] ?? '' }}
                                        <div class="text-[9px] font-mono text-slate-400 mt-0.5">{{ $p['student']['matricule'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-300">
                                        {{ $p['student']['school_class']['name'] ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ $p['fee_type']['name'] ?? 'N/A' }}
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                         @php
                                             $method = $p['payment_method'] ?? 'Cash';
                                             $methodBadgeClass = '';
                                             $methodIcon = '';
                                             if ($method === 'Cash') {
                                                 $methodBadgeClass = 'bg-emerald-100 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30';
                                                 $methodIcon = 'banknote';
                                             } elseif ($method === 'Card') {
                                                 $methodBadgeClass = 'bg-blue-100 dark:bg-blue-950/20 text-blue-800 dark:text-blue-400 border border-blue-200 dark:border-blue-800/30';
                                                 $methodIcon = 'credit-card';
                                             } elseif ($method === 'Mobile Money') {
                                                 $methodBadgeClass = 'bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30';
                                                 $methodIcon = 'smartphone';
                                             } else {
                                                 $methodBadgeClass = 'bg-indigo-100 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/30';
                                                 $methodIcon = 'landmark';
                                             }
                                         @endphp
                                         <span class="px-2 py-0.5 rounded-lg font-bold text-[10px] inline-flex items-center gap-1 {{ $methodBadgeClass }}">
                                             <i data-lucide="{{ $methodIcon }}" class="w-3.5 h-3.5"></i>
                                             {{ $method }}
                                         </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs font-mono text-slate-400">
                                        {{ $p['reference'] }}
                                    </td>
                                    <td class="py-3 px-4 text-sm font-bold text-slate-900 dark:text-white text-right">
                                        ${{ number_format($p['amount'], 2) }}
                                        <div class="text-[10px] text-slate-400 font-medium">{{ number_format($p['amount'] * 2500, 0, ',', ' ') }} FC</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 text-sm">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="receipt" class="w-10 h-10 text-slate-300"></i>
                                            <span>Aucune transaction correspondante dans ce rapport.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($activeTab === 'salaires')
            <!-- Salaires & Dépenses / Décaissements Table -->
            <div class="flex-1 flex flex-col h-full overflow-hidden">
                <!-- Totals Box for Sorties -->
                <div class="p-4 border-b border-slate-200 dark:border-[#333] flex justify-between items-center gap-4 bg-slate-50/50 dark:bg-[#111]/30">
                    <div class="flex gap-6">
                        <div class="text-xs text-slate-500">
                            Total Décaissements Validés : <span class="font-bold text-emerald-600">${{ number_format($totalApprovedSorties, 2) }} / {{ number_format($totalApprovedSorties * 2500, 0, ',', ' ') }} FC</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            Total En Attente : <span class="font-bold text-amber-600">${{ number_format($totalPendingSorties, 2) }} / {{ number_format($totalPendingSorties * 2500, 0, ',', ' ') }} FC</span>
                        </div>
                    </div>
                    <div class="text-xs text-slate-500">
                        Total Opérations : <span class="font-bold text-slate-800 dark:text-white">{{ count($payments) }}</span>
                    </div>
                </div>

                <div class="flex-1 overflow-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Référence</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Bénéficiaire</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Motif</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Montant</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                            @forelse($payments as $p)
                                <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                                    <td class="py-3 px-4 text-sm text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') }}</td>
                                    <td class="py-3 px-4 text-sm text-slate-500 font-mono">{{ $p['reference'] }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-slate-900 dark:text-white">
                                        @if ($p['teacher'])
                                            {{ $p['teacher']['first_name'] }} {{ $p['teacher']['last_name'] }} <span class="text-[10px] text-indigo-500 bg-indigo-50 dark:bg-indigo-950/20 px-1.5 py-0.5 rounded ml-1 font-semibold">Enseignant</span>
                                        @else
                                            {{ $p['beneficiary_name'] ?? 'Externe / Autre' }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm text-slate-500">
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-700 dark:bg-[#222] dark:text-slate-300 rounded text-xs">{{ $p['motif'] ?? 'Autre' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-sm font-bold text-rose-600">
                                        -${{ number_format($p['amount'], 2) }}
                                        <div class="text-[10px] text-rose-400 font-normal">{{ number_format($p['amount'] * 2500, 0, ',', ' ') }} FC</div>
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        @if($p['status'] === 'approved')
                                            <span class="px-2.5 py-1 text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 rounded-full flex items-center justify-center gap-1 w-max">
                                                ✔ Validé
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-bold bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 rounded-full flex items-center justify-center gap-1 w-max">
                                                ⚠ En attente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Super Admin Validation Button -->
                                            @if($p['status'] === 'pending' && (\Illuminate\Support\Facades\Auth::user()?->hasRole('Super Admin') || \Illuminate\Support\Facades\Auth::user()?->type === 'super_admin'))
                                                <button wire:click="approvePayment({{ $p['id'] }})" class="px-2 py-1 text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded transition-colors flex items-center gap-1" title="Approuver le décaissement">
                                                    Valider
                                                </button>
                                            @endif

                                            <!-- PDF Export Button -->
                                            <button wire:click="downloadPdf({{ $p['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors" title="Exporter en PDF">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button wire:click="deletePayment({{ $p['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Supprimer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500 text-sm">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="inbox" class="w-8 h-8 mb-2 opacity-50"></i>
                                            Aucun décaissement trouvé.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($activeTab === 'situation_eleves')
            <!-- Student Situation Tab -->
            <div class="flex-1 flex flex-col h-full overflow-hidden">
                <!-- Search and Stats -->
                <div class="p-4 border-b border-slate-200 dark:border-[#333] flex justify-between items-center gap-4 bg-slate-50/50 dark:bg-[#111]/30">
                    <div class="relative w-72">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"></i>
                        <input type="text" wire:model.live="searchSituation" placeholder="Rechercher élève..." class="block w-full py-1.5 pl-9 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                    </div>
                    <div class="text-xs text-slate-500">
                        Total élèves suivis : <span class="font-bold text-slate-800 dark:text-white">{{ count($studentsSituation) }}</span>
                    </div>
                </div>

                <!-- Table -->
                <div class="flex-1 overflow-auto" x-data="{ expanded: {} }">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333] sticky top-0 z-10">
                            <tr>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Élève</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Classe</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Frais Dûs</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Payé</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Avances</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Solde Restant</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Statut</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                           <tbody class="divide-y divide-slate-200 dark:divide-[#333]">
                            @php
                                $groupedSituations = collect($studentsSituation)->groupBy('class_name');
                            @endphp
                            @forelse($groupedSituations as $className => $rows)
                                <!-- Group Header Row -->
                                <tr class="bg-indigo-50/20 dark:bg-indigo-950/10 border-y border-slate-200/80 dark:border-slate-800">
                                    <td colspan="8" class="py-2.5 px-4 text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="layers" class="w-4 h-4 text-indigo-500"></i>
                                        <span>Classe : {{ $className }}</span>
                                        <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 rounded-full text-[10px] font-bold lowercase">{{ count($rows) }} élève(s)</span>
                                    </td>
                                </tr>
                                @foreach($rows as $row)
                                    @php
                                        $rowClass = '';
                                        if ($row['status'] === 'En retard') {
                                            $rowClass = 'bg-rose-500/[0.03] dark:bg-rose-500/[0.01] border-l-[3px] border-l-rose-500';
                                        } elseif ($row['status'] === 'Dérogation') {
                                            $rowClass = 'bg-amber-500/[0.02] dark:bg-amber-500/[0.005] border-l-[3px] border-l-amber-500';
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors border-b border-slate-100 dark:border-[#222] {{ $rowClass }}">
                                        <td class="py-3 px-4">
                                            <button @click="expanded[{{ $row['id'] }}] = !expanded[{{ $row['id'] }}]" class="text-slate-400 hover:text-slate-600 focus:outline-none flex items-center gap-1.5 text-left">
                                                <span class="font-medium text-slate-900 dark:text-white hover:underline">{{ $row['name'] }}</span>
                                                <svg class="w-3.5 h-3.5 transition-transform" :class="expanded[{{ $row['id'] }}] ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <div class="text-[10px] text-slate-400 font-mono pl-0.5">{{ $row['matricule'] }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-slate-600 dark:text-slate-300">{{ $row['class_name'] }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">${{ number_format($row['due_usd'], 2) }}</div>
                                            <div class="text-[10px] text-slate-400 font-medium">{{ number_format($row['due_cdf'], 0, ',', ' ') }} FC</div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="text-sm font-semibold text-emerald-600">${{ number_format($row['paid_usd'], 2) }}</div>
                                            <div class="text-[10px] text-emerald-500 font-medium">{{ number_format($row['paid_cdf'], 0, ',', ' ') }} FC</div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($row['advance_usd'] > 0)
                                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-950/20 dark:text-indigo-400 rounded text-xs font-bold">${{ number_format($row['advance_usd'], 2) }}</span>
                                                <div class="text-[9px] text-indigo-400 font-medium mt-0.5">{{ number_format($row['advance_cdf'], 0, ',', ' ') }} FC</div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="text-sm font-bold {{ $row['remaining_usd'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">${{ number_format($row['remaining_usd'], 2) }}</div>
                                            <div class="text-[10px] {{ $row['remaining_usd'] > 0 ? 'text-rose-400' : 'text-emerald-500' }} font-medium">{{ number_format($row['remaining_cdf'], 0, ',', ' ') }} FC</div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($row['status'] === 'En règle')
                                                <span class="px-2.5 py-1 text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 rounded-full flex items-center justify-center gap-1 w-max mx-auto">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> En règle
                                                </span>
                                            @elseif($row['status'] === 'Exonéré')
                                                <span class="px-2.5 py-1 text-[11px] font-bold bg-indigo-100 dark:bg-indigo-950/20 text-indigo-800 dark:text-indigo-400 rounded-full flex items-center justify-center gap-1 w-max mx-auto">
                                                    <i data-lucide="gift" class="w-3.5 h-3.5"></i> Exonéré (Enseignant)
                                                </span>
                                            @elseif($row['status'] === 'Dérogation')
                                                <span class="px-2.5 py-1 text-[11px] font-bold bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 rounded-full flex items-center justify-center gap-1 w-max mx-auto" title="{{ $row['derogation_notes'] }}">
                                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Dérogation
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 text-[11px] font-bold bg-rose-100 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 rounded-full flex items-center justify-center gap-1 w-max mx-auto">
                                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> En retard
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="openPaymentModalForStudent({{ $row['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors" title="Nouvel Encaissement (Pré-rempli)">
                                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                                </button>
                                                <button wire:click="openCardPaymentModal({{ $row['id'] }})" class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors" title="Payer par carte (Simulation pré-remplie)">
                                                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                                                </button>
                                                <button wire:click="sendWhatsAppReminder({{ $row['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors" title="Rappel automatique WhatsApp">
                                                    <i data-lucide="bell" class="w-4 h-4"></i>
                                                </button>
                                                @if($row['status'] === 'En retard')
                                                    <button wire:click="sendLatePaymentAlert({{ $row['id'] }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-colors" title="Alerte Retard Frais (Premier)">
                                                        <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                                                    </button>
                                                @endif
                                                <button wire:click="openDerogationModal({{ $row['id'] }})" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222] transition-colors" title="Dérogations & Exonérations">
                                                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Tranche Accordion Details -->
                                    <tr x-show="expanded[{{ $row['id'] }}]" style="display: none;" class="bg-slate-50/50 dark:bg-[#111]/10">
                                        <td colspan="8" class="p-4 border-t border-b border-slate-200 dark:border-[#222]">
                                            <div class="px-5 py-4 bg-white dark:bg-[#0c0c0c] rounded-xl border border-slate-200 dark:border-[#222] space-y-3 shadow-inner">
                                                <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                    Détail des Tranches de Frais
                                                </h4>
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                    @foreach($row['tranches'] as $tranche)
                                                        <div class="p-3 border border-slate-100 dark:border-[#222] rounded-xl bg-slate-50/30 dark:bg-[#0a0a0a]/50 flex flex-col justify-between gap-3 shadow-sm">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <div>
                                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $tranche['name'] }}</span>
                                                                    <span class="block text-[9px] text-slate-400 mt-0.5">Taux: 1$ = {{ number_format($tranche['exchange_rate'], 0, ',', ' ') }} FC</span>
                                                                </div>
                                                                
                                                                <!-- Status Badges -->
                                                                @if($tranche['status'] === 'Soldé')
                                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400 rounded">Soldé</span>
                                                                @elseif($tranche['status'] === 'Acompte')
                                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400 rounded">Acompte</span>
                                                                @elseif($tranche['status'] === 'Avance')
                                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/30 dark:text-blue-400 rounded">Avance</span>
                                                                @elseif($tranche['status'] === 'Exonéré')
                                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-400 rounded">Exonéré</span>
                                                                @else
                                                                    <span class="px-2 py-0.5 text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400 rounded">Non payé</span>
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="grid grid-cols-3 gap-2 border-t border-slate-100 dark:border-[#222] pt-2 text-[10px]">
                                                                <div>
                                                                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider">Montant Fixé</span>
                                                                    <span class="font-semibold text-slate-700 dark:text-slate-300">${{ number_format($tranche['fixed_amount_usd'], 2) }}</span>
                                                                    <span class="block text-[9px] text-slate-400 mt-0.5">{{ number_format($tranche['fixed_amount_cdf'], 0, ',', ' ') }} FC</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider">Payé</span>
                                                                    <span class="font-semibold text-emerald-600">${{ number_format($tranche['paid_amount_usd'], 2) }}</span>
                                                                    <span class="block text-[9px] text-emerald-500 mt-0.5">{{ number_format($tranche['paid_amount_cdf'], 0, ',', ' ') }} FC</span>
                                                                </div>
                                                                <div>
                                                                    <span class="block text-[8px] text-slate-400 uppercase tracking-wider">Reste</span>
                                                                    <span class="font-bold {{ $tranche['remaining_amount_usd'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">${{ number_format($tranche['remaining_amount_usd'], 2) }}</span>
                                                                    <span class="block text-[9px] {{ $tranche['remaining_amount_usd'] > 0 ? 'text-rose-400' : 'text-emerald-500' }} font-medium mt-0.5">{{ number_format($tranche['remaining_amount_cdf'], 0, ',', ' ') }} FC</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-500 text-sm">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="users" class="w-8 h-8 mb-2 opacity-50"></i>
                                            Aucun élève trouvé.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- OHADA Tab -->
            <div class="p-8 space-y-6">
                <!-- Date Filters Toolbar -->
                <div class="p-4 bg-slate-50 dark:bg-[#111] border border-slate-200/60 dark:border-[#222] rounded-2xl flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date de Début :</span>
                        <input type="date" wire:model.live="ohada_start_date" class="py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Date de Fin :</span>
                        <input type="date" wire:model.live="ohada_end_date" class="py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
                        <div class="text-sm font-medium text-emerald-800 dark:text-emerald-400 mb-1">Classe 7 - Produits (Revenus)</div>
                        <div class="text-3xl font-bold text-emerald-900 dark:text-emerald-300">${{ number_format($totalRevenus, 2) }}</div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-500 mt-1 font-semibold">{{ number_format($totalRevenus * 2500, 0, ',', ' ') }} FC</div>
                    </div>
                    <div class="bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800 rounded-2xl p-6">
                        <div class="text-sm font-medium text-rose-800 dark:text-rose-400 mb-1">Classe 6 - Charges (Dépenses)</div>
                        <div class="text-3xl font-bold text-rose-900 dark:text-rose-300">${{ number_format($totalCharges, 2) }}</div>
                        <div class="text-xs text-rose-600 dark:text-rose-500 mt-1 font-semibold">{{ number_format($totalCharges * 2500, 0, ',', ' ') }} FC</div>
                    </div>
                </div>

                <div class="p-6 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#333] rounded-2xl flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-500 mb-1">Résultat Net (Produits - Charges)</div>
                        @php $resultat = $totalRevenus - $totalCharges; @endphp
                        <div class="text-3xl font-bold {{ $resultat >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $resultat >= 0 ? '+' : '' }}${{ number_format($resultat, 2) }}
                        </div>
                        <div class="text-xs font-semibold {{ $resultat >= 0 ? 'text-emerald-500' : 'text-rose-500' }} mt-1">
                            {{ $resultat >= 0 ? '+' : '' }}{{ number_format($resultat * 2500, 0, ',', ' ') }} FC
                        </div>
                    </div>
                    <button type="button" wire:click="openOhadaFilterModal" class="px-4 py-2 bg-white dark:bg-[#222] border border-slate-200 dark:border-[#444] text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-[#333] transition-colors shadow-sm flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Générer Rapport OHADA
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Offcanvas Form -->
    <div x-data="{ open: @entangle('isPaymentModalOpen') }"
         class="fixed inset-0 z-50 flex justify-end"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Panel -->
        <div class="relative w-full max-w-md bg-white dark:bg-[#0a0a0a] h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-[#333]"
             x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-[#1a1a1a]">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ $form_transaction_type === 'Entrée' ? 'Nouvel Encaissement' : 'Nouveau Décaissement' }}
                </h2>
                <button @click="open = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <form wire:submit.prevent="savePayment" class="space-y-5">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type de Transaction</label>
                        <select wire:model="form_transaction_type" disabled class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed transition-all">
                            <option value="Entrée">Entrée (Encaissement)</option>
                            <option value="Sortie">Sortie (Décaissement)</option>
                        </select>
                        @error('form_transaction_type') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if ($form_transaction_type === 'Entrée')
                        <!-- Step 1: Select Class -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Classe</label>
                            <select wire:model.live="form_class_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="">Sélectionner une classe</option>
                                @foreach($schoolClasses as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                                @endforeach
                            </select>
                            @error('form_class_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Step 2: Select Student in Class -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Élève qui va payer</label>
                            <select wire:model.live="form_student_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all" {{ empty($form_class_id) ? 'disabled' : '' }}>
                                <option value="">
                                    {{ empty($form_class_id) ? 'Sélectionnez une classe d\'abord' : 'Sélectionner un élève' }}
                                </option>
                                @if(!empty($form_class_id))
                                    @php
                                        $classStudents = collect($students)->where('school_class_id', intval($form_class_id))->sortBy('last_name');
                                    @endphp
                                    @foreach($classStudents as $s)
                                        <option value="{{ $s['id'] }}">{{ $s['first_name'] }} {{ $s['last_name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('form_student_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type de Frais</label>
                            <select wire:model.live="form_fee_type_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="">Sélectionner</option>
                                @foreach($feeTypes as $f)
                                    <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Dynamic Fee calculations (USD and CDF) -->
                        @if ($form_student_id && $form_fee_type_id)
                            <div class="p-3 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#222] rounded-xl text-xs space-y-2 mt-1">
                                <div class="flex justify-between items-center text-slate-500">
                                    <span>Montant fixé :</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">${{ number_format($form_fixed_amount_usd, 2) }} / {{ number_format($form_fixed_amount_cdf, 0, ',', ' ') }} FC</span>
                                </div>
                                <div class="flex justify-between items-center text-slate-500">
                                    <span>Déjà payé :</span>
                                    <span class="font-bold text-emerald-600">${{ number_format($form_paid_amount_usd, 2) }} / {{ number_format($form_paid_amount_cdf, 0, ',', ' ') }} FC</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-200 dark:border-[#222] pt-2 text-slate-900 dark:text-white font-bold">
                                    <span>Reste à payer :</span>
                                    <span class="{{ $form_remaining_amount_usd > 0 ? 'text-rose-600' : 'text-emerald-600' }}">${{ number_format($form_remaining_amount_usd, 2) }} / {{ number_format($form_remaining_amount_cdf, 0, ',', ' ') }} FC</span>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Sortie (Décaissement) Fields -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type de Bénéficiaire</label>
                            <select wire:model.live="form_beneficiary_type" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="Enseignant">Enseignant / Personnel</option>
                                <option value="Autre">Autre (Fournisseur, Externe, etc.)</option>
                            </select>
                        </div>

                        @if ($form_beneficiary_type === 'Enseignant')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bénéficiaire (Enseignant)</label>
                                <select wire:model="form_teacher_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="">Sélectionner un enseignant</option>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t['id'] }}">{{ $t['first_name'] }} {{ $t['last_name'] }}</option>
                                    @endforeach
                                </select>
                                @error('form_teacher_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom du Bénéficiaire</label>
                                <input type="text" placeholder="Ex: Fournisseur, Bureau, Transport, etc." wire:model="form_beneficiary_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                @error('form_beneficiary_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Motif du Décaissement</label>
                            <select wire:model="form_motif" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="Achat">Achat Fournitures / Équipement</option>
                                <option value="Transport">Transport / Logistique</option>
                                <option value="Salaires">Salaires / Rémunération</option>
                                <option value="Loyer">Loyer / Factures récurrentes</option>
                                <option value="Maintenance">Maintenance & Travaux</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    @endif

                    @if ($form_transaction_type === 'Entrée')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Moyen de Paiement</label>
                            <select wire:model="form_payment_method" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="Cash">Espèces (Cash)</option>
                                <option value="Card">Carte Bancaire</option>
                                <option value="Bank Transfer">Virement Bancaire</option>
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant ($)</label>
                            <input type="number" step="0.01" wire:model="form_amount" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('form_amount') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date</label>
                            <input type="date" wire:model="form_payment_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('form_payment_date') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Compte OHADA</label>
                        <input type="text" wire:model="form_ohada_code" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description (Optionnelle)</label>
                        <textarea wire:model="form_description" rows="3" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-[#1a1a1a]">
                        <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Card Payment Modal -->
    <div x-data="{ open: @entangle('isCardPaymentModalOpen') }"
         class="fixed inset-0 z-50 flex justify-end"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Panel -->
        <div class="relative w-full max-w-md bg-white dark:bg-[#0a0a0a] h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-[#333]"
             x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-[#1a1a1a]">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Paiement par Carte Bancaire</h2>
                <button @click="open = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                @if($selectedStudent)
                    <!-- Student Summary Card -->
                    <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl mb-5">
                        <div class="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-bold mb-1">Élève</div>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $selectedStudent['first_name'] }} {{ $selectedStudent['last_name'] }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">Matricule: {{ $selectedStudent['matricule'] }}</div>
                        
                        @if(strtolower($selectedStudent['first_name']) === 'walter')
                            <div class="mt-3 p-2 bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 rounded-lg text-xs flex items-start gap-1.5">
                                <i data-lucide="info" class="w-4.5 h-4.5 mt-0.5 shrink-0"></i>
                                <span><strong>Mode Démo :</strong> Les coordonnées de carte de l'élève Walter ont été pré-remplies.</span>
                            </div>
                        @endif
                    </div>
                @endif

                <form wire:submit.prevent="saveCardPayment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Type de Frais</label>
                        <select wire:model="card_fee_type_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            <option value="">Sélectionner</option>
                            @foreach($feeTypes as $f)
                                <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                            @endforeach
                        </select>
                        @error('card_fee_type_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Montant à prélever ($)</label>
                        <input type="number" step="0.01" wire:model="card_amount" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        @error('card_amount') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom sur la Carte</label>
                        <input type="text" wire:model="card_holder_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        @error('card_holder_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Numéro de Carte</label>
                        <div class="relative">
                            <input type="text" placeholder="xxxx xxxx xxxx xxxx" wire:model="card_number" class="block w-full py-2 pl-3 pr-10 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            <i data-lucide="credit-card" class="w-4 h-4 absolute right-3 top-3 text-slate-400"></i>
                        </div>
                        @error('card_number') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Expiration</label>
                            <input type="text" placeholder="MM/AA" wire:model="card_expiry" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('card_expiry') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">CVV</label>
                            <input type="text" placeholder="123" wire:model="card_cvv" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @error('card_cvv') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-[#1a1a1a]">
                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center justify-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4"></i> Valider le paiement par Carte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Derogation & Exemption Modal -->
    <div x-data="{ open: @entangle('isDerogationModalOpen') }"
         class="fixed inset-0 z-50 flex justify-end"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Panel -->
        <div class="relative w-full max-w-md bg-white dark:bg-[#0a0a0a] h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-[#333]"
             x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-[#1a1a1a]">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Dérogations & Exonérations</h2>
                <button @click="open = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                @if($selectedStudent)
                    <div class="p-4 bg-slate-50 dark:bg-[#111] border border-slate-100 dark:border-[#222] rounded-xl mb-5">
                        <div class="font-bold text-slate-900 dark:text-white">{{ $selectedStudent['first_name'] }} {{ $selectedStudent['last_name'] }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">Matricule: {{ $selectedStudent['matricule'] }}</div>
                    </div>
                @endif

                <form wire:submit.prevent="saveDerogation" class="space-y-5">
                    
                    <!-- Exoneration -->
                    <div class="p-4 border border-slate-100 dark:border-[#222] rounded-xl space-y-3 bg-slate-50/30">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="gift" class="w-4 h-4 text-indigo-500"></i> Exonération Enseignant
                        </h3>
                        <p class="text-xs text-slate-500">Exonère totalement (100% de réduction) les enfants d'enseignants ou de membres du personnel.</p>
                        
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none pt-1">
                            <input type="checkbox" wire:model="derogation_is_teacher_child" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Enfant d'enseignant (Exonéré)</span>
                        </label>
                    </div>

                    <!-- Derogation -->
                    <div class="p-4 border border-slate-100 dark:border-[#222] rounded-xl space-y-3 bg-slate-50/30">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="file-text" class="w-4 h-4 text-amber-500"></i> Dérogation de Paiement
                        </h3>
                        <p class="text-xs text-slate-500">Permet à l'élève d'accéder aux cours malgré un solde débiteur en retard.</p>
                        
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none pt-1">
                            <input type="checkbox" wire:model="derogation_has_derogation" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Accorder une dérogation</span>
                        </label>

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Notes de dérogation</label>
                            <textarea wire:model="derogation_notes" rows="2" placeholder="Ex: Accord de paiement d'ici la fin du mois..." class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                        </div>
                    </div>

                    <!-- Advances / Credits -->
                    <div class="p-4 border border-slate-100 dark:border-[#222] rounded-xl space-y-3 bg-slate-50/30">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-500"></i> Solde d'Avance (Crédit)
                        </h3>
                        <p class="text-xs text-slate-500">Solde créditeur disponible pour payer de futurs frais scolaires.</p>
                        
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Montant d'avance ($)</label>
                            <input type="number" step="0.01" wire:model="derogation_advance_balance" class="block w-full py-1.5 px-2.5 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-[#1a1a1a]">
                        <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                            Mettre à jour le statut
                        </button>
                    </div>
                                </form>
            </div>
        </div>
    </div>

    <!-- OHADA Filter Modal -->
    <div x-data="{ open: @entangle('isOhadaFilterModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-sm bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in"
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
                    <i data-lucide="filter" class="w-4 h-4 text-emerald-500"></i> Exporter Rapport OHADA
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Début</label>
                        <input type="date" wire:model="ohada_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-emerald-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Fin</label>
                        <input type="date" wire:model="ohada_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-emerald-500 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="exportOhadaPdf" class="flex-1 py-2 text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i> Télécharger
                </button>
            </div>
        </div>
    </div>

    <!-- Rapport de Caisse Filter Modal -->
    <div x-data="{ open: @entangle('isRapportFilterModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-md bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in"
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
                    <i data-lucide="filter" class="w-4 h-4 text-indigo-500"></i> Filtre Limite du Rapport
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Début</label>
                        <input type="date" wire:model="rapport_start_date" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Fin</label>
                        <input type="date" wire:model="rapport_end_date" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Filtrer par Classe</label>
                    <select wire:model="rapport_class_id" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="">Toutes les classes</option>
                        @foreach($schoolClasses as $class)
                            <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Filtrer par Type de Frais</label>
                    <select wire:model="rapport_fee_type_id" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="">Tous les frais</option>
                        @foreach($feeTypes as $fee)
                            <option value="{{ $fee['id'] }}">{{ $fee['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Filtrer par Méthode de Paiement</label>
                    <select wire:model="rapport_payment_method" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="">Toutes les méthodes</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Carte Bancaire</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Bank Transfer">Virement Bancaire</option>
                    </select>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" wire:click="resetRapportFilter" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Réinitialiser
                </button>
                <button type="button" wire:click="applyRapportFilter" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Appliquer
                </button>
            </div>
        </div>
    </div>
</div>
