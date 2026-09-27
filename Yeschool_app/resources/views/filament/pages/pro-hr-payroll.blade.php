<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Rémunération & Bulletins Paie</h1>
            <p class="text-slate-500 text-sm mt-1 font-space font-medium">Gérez l'émission des bulletins de paie, les primes, les retenues et les états de salaire.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="openGlobalReportModal" class="px-4 py-2 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#111] text-slate-700 dark:text-slate-200 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i>
                Rapport de Paie
            </button>
            <button type="button" wire:click="openAnalysisModal" class="px-4 py-2 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#111] text-slate-700 dark:text-slate-200 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="trending-up" class="w-4 h-4 text-indigo-500"></i>
                Analyse
            </button>
            <button type="button" wire:click="createPayslip" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Émettre Bulletin
            </button>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
        <div class="grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-[#1a1a1a]">
            
            <!-- Left: Payslips list -->
            <div class="lg:col-span-2 overflow-x-auto flex flex-col">
                <div class="p-4 bg-slate-50/50 dark:bg-[#111]/30 border-b border-slate-100 dark:border-[#1a1a1a] flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bulletins de Paie émis</h3>
                    <button type="button" wire:click="openDetailedPayrollModal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-sm transition-all">
                        <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i> Fiche Détaillée
                    </button>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/50 dark:bg-[#0c0c0c] border-b border-slate-100 dark:border-[#1a1a1a]">
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
                                        <button type="button" wire:click="promptDelete({{ $ps['id'] }}, 'deletePayslip', 'Êtes-vous sûr de vouloir supprimer définitivement ce bulletin de paie ? Cette action est irréversible.')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
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
                <h3 class="text-sm font-bold text-indigo-950 dark:text-indigo-400 flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3 font-space">
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

                    @if($simulated_teacher_id)
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Salaire de Base ($)</label>
                            <input type="number" step="0.01" wire:model.live="simulated_base_salary" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Primes & Bonus ($)</label>
                        <input type="number" step="0.01" wire:model.live="simulated_bonus" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Retenues & Déductions ($)</label>
                        <input type="number" step="0.01" wire:model.live="simulated_deductions" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>

                    <div class="mt-6 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 text-center">
                        <span class="text-xs text-indigo-700 dark:text-indigo-400 font-semibold block mb-1 font-space">Estimation Salaire Net</span>
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 font-space">{{ number_format($simulated_net, 2, ',', ' ') }} $</span>
                            @php
                                $exchangeRate = floatval(\App\Models\Setting::get('exchange_rate', '2500.0'));
                                $simulatedNetCdf = $simulated_net * $exchangeRate;
                            @endphp
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-space">
                                soit {{ number_format($simulatedNetCdf, 0, ',', ' ') }} CDF <span class="text-[10px] text-slate-400 font-sans">(Taux: 1$ = {{ number_format($exchangeRate, 0, ',', ' ') }} FC)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SIDE-OVER: PAYSLIP FORM -->
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
                                    <td class="px-6 py-4 text-indigo-950 dark:text-indigo-400 font-space">Total Net à Payer</td>
                                    <td colspan="2" class="px-6 py-4 text-right text-lg text-indigo-600 dark:text-indigo-400 font-space">
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
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 font-space">
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

    <!-- MODAL: GLOBAL PAYROLL REPORT -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isGlobalReportModalOpen') }"
         x-show="open"
         x-cloak>
        
        <!-- Backdrop clickable -->
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
                    <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i> Rapport Masse Salariale
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">Exportez le rapport global de la masse salariale calculant les totaux des salaires, primes et déductions.</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Début</label>
                        <input type="date" wire:model="report_start_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Date de Fin</label>
                        <input type="date" wire:model="report_end_date" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="exportGlobalPayrollPdf" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Exporter PDF
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: PAYROLL ANALYSIS -->
    <div class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         x-data="{ open: @entangle('isAnalysisModalOpen') }"
         x-show="open"
         x-cloak>
        
        <!-- Backdrop clickable -->
        <div class="absolute inset-0" @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-4xl bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4 flex flex-col max-h-[90vh]"
             x-show="open"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-[#1a1a1a] shrink-0">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 font-space">
                    <i data-lucide="trending-up" class="w-4 h-4 text-indigo-500"></i> Analyse Financière de la Paie
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                
                <!-- Main metric totals -->
                @php
                    $chartHistArr = $chartHistory;
                    $currentMonthPayroll = count($chartHistArr) > 0 ? end($chartHistArr)['total_net'] : 0;
                    $currentMonthBonus = count($chartHistArr) > 0 ? end($chartHistArr)['total_bonus'] : 0;
                    $currentMonthDeductions = count($chartHistArr) > 0 ? end($chartHistArr)['total_deductions'] : 0;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl border border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/30">
                        <span class="text-xs font-semibold text-slate-500 block mb-1">Masse Salariale Net (Ce Mois)</span>
                        <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400 font-space">{{ number_format($currentMonthPayroll, 2, ',', ' ') }} $</span>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/30">
                        <span class="text-xs font-semibold text-slate-500 block mb-1">Total Primes Versées (Ce Mois)</span>
                        <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-space">+{{ number_format($currentMonthBonus, 2, ',', ' ') }} $</span>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/30">
                        <span class="text-xs font-semibold text-slate-500 block mb-1">Total Déductions Appliquées</span>
                        <span class="text-xl font-bold text-rose-600 dark:text-rose-400 font-space">-{{ number_format($currentMonthDeductions, 2, ',', ' ') }} $</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Evolution Chart -->
                    <div class="p-5 border border-slate-100 dark:border-[#1a1a1a] rounded-xl bg-white dark:bg-[#0c0c0c] space-y-4">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-space">Évolution Mensuelle des Coûts</h4>
                        <div x-data="{
                            init() {
                                const history = @js($chartHistory);
                                const categories = history.map(h => h.month_year);
                                const data = history.map(h => h.total_net);
                                const options = {
                                    series: [{ name: 'Masse Salariale Net', data: data }],
                                    chart: { type: 'area', height: 260, toolbar: { show: false } },
                                    colors: ['#4f46e5'],
                                    fill: {
                                        type: 'gradient',
                                        gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 }
                                    },
                                    stroke: { curve: 'smooth', width: 3 },
                                    xaxis: { categories: categories, labels: { style: { colors: '#94a3b8' } } },
                                    yaxis: { labels: { style: { colors: '#94a3b8' } } },
                                    grid: { borderColor: '#e2e8f033' }
                                };
                                const chart = new ApexCharts(this.$refs.evolChart, options);
                                chart.render();
                            }
                        }" class="w-full">
                            <div x-ref="evolChart" class="w-full h-[260px]"></div>
                        </div>
                    </div>

                    <!-- Specialty breakdown Chart -->
                    <div class="p-5 border border-slate-100 dark:border-[#1a1a1a] rounded-xl bg-white dark:bg-[#0c0c0c] space-y-4">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-space">Répartition par Spécialité</h4>
                        <div x-data="{
                            init() {
                                const spec = @js($chartSpecialties);
                                const labels = spec.map(s => s.specialty);
                                const data = spec.map(s => s.total_net);
                                const options = {
                                    series: data,
                                    chart: { type: 'donut', height: 260 },
                                    labels: labels,
                                    colors: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                                    legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
                                    stroke: { show: false }
                                };
                                const chart = new ApexCharts(this.$refs.specChart, options);
                                chart.render();
                            }
                        }" class="w-full flex justify-center">
                            <div x-ref="specChart" class="w-full max-w-[320px] h-[260px]"></div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3 justify-end shrink-0">
                <button type="button" @click="open = false" class="px-6 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Fermer
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

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('morph.updated', ({ el, component }) => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
</script>
