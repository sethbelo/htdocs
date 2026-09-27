<div class="space-y-6 animate-fade-in pb-12">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 mb-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                <a href="{{ \App\Filament\Pages\ProDashboard::getUrl() }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-indigo-600 dark:text-indigo-400">Pédagogie</span>
            </div>
            <h1 class="text-3xl font-bold font-space text-slate-900 dark:text-white">Présences & Alertes Parents</h1>
        </div>
    </div>

    <!-- QUICK STATS OVERVIEW -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <!-- Week Rate -->
        <div class="glass-panel p-5 relative overflow-hidden flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Cette semaine</span>
                <span class="text-3xl font-bold font-space text-indigo-600 dark:text-indigo-400">{{ $attendanceRateWeek }}%</span>
                <span class="text-xs text-slate-400 block">Taux d'assiduité moyen</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Month Rate -->
        <div class="glass-panel p-5 relative overflow-hidden flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Ce mois</span>
                <span class="text-3xl font-bold font-space text-emerald-600 dark:text-emerald-400">{{ $attendanceRateMonth }}%</span>
                <span class="text-xs text-slate-400 block">Taux d'assiduité moyen</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Year Rate -->
        <div class="glass-panel p-5 relative overflow-hidden flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Année Scolaire</span>
                <span class="text-3xl font-bold font-space text-purple-600 dark:text-purple-400">{{ $attendanceRateYear }}%</span>
                <span class="text-xs text-slate-400 block">Taux d'assiduité annuel</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Absences Count -->
        <div class="glass-panel p-5 relative overflow-hidden flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Absences cumulées</span>
                <span class="text-3xl font-bold font-space text-rose-600 dark:text-rose-400">{{ $totalAbsentYear }}</span>
                <span class="text-xs text-slate-400 block">{{ $totalJustifiedYear }} justifiées par les parents</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- TABS Navigation -->
    <div class="flex gap-2 border-b border-slate-200 dark:border-[#333] no-print">
        <button wire:click="$set('activeTab', 'appel')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'appel' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:white' }}">
            <i data-lucide="check-square" class="w-4 h-4"></i>
            Feuille d'appel
        </button>
        <button wire:click="$set('activeTab', 'grille_mensuelle')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'grille_mensuelle' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:white' }}">
            <i data-lucide="grid" class="w-4 h-4"></i>
            Grille Mensuelle
        </button>
        <button wire:click="$set('activeTab', 'statistiques')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'statistiques' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:white' }}">
            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
            Analyses & Rapports
        </button>
        @if($this->is_head_teacher)
        <button wire:click="$set('activeTab', 'logs')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'logs' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:white' }}">
            <i data-lucide="message-square" class="w-4 h-4"></i>
            Historique WhatsApp
        </button>
        @endif
        @if(auth()->user()->hasRole('Super Admin'))
        <button wire:click="$set('activeTab', 'whatsapp_settings')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'whatsapp_settings' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:white' }}">
            <i data-lucide="settings" class="w-4 h-4"></i>
            Configuration WhatsApp
        </button>
        @endif
    </div>

    <!-- FLASH MESSAGES -->
    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl flex items-center gap-3 no-print">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 rounded-xl flex items-center gap-3 no-print">
            <i data-lucide="alert-octagon" class="w-5 h-5"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- MAIN PANEL -->
    <div class="glass-panel overflow-hidden">
        
        <!-- TAB 1: FEUILLE D'APPEL -->
        @if($activeTab === 'appel')
            @if(empty($schoolClasses))
                <div class="p-12 text-center flex flex-col items-center justify-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-[#111] flex items-center justify-center text-slate-400 dark:text-slate-500">
                        <i data-lucide="alert-circle" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Aucune classe disponible</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        La feuille d'appel est réservée aux enseignants titulaires (responsables) de classe. Aucune classe ne vous est assignée en tant que titulaire pour cette année scolaire.
                    </p>
                </div>
            @else
                <div class="p-5 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 flex flex-wrap gap-4 items-end justify-between no-print">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Classe</label>
                            <select wire:model.live="selectedClassId" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                @foreach($schoolClasses as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date d'appel</label>
                            <input type="date" wire:model.live="selectedDate" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none mr-3">
                            <input type="checkbox" wire:model="autoSendWhatsApp" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                            <span class="text-sm font-semibold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                <i data-lucide="zap" class="w-4 h-4 text-amber-500 fill-amber-500"></i>
                                Alerte automatique WhatsApp
                            </span>
                        </label>
                        <button wire:click="saveAttendances" class="px-5 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                        </button>
                        <button wire:click="exportAttendance" class="px-4 py-2 text-sm font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] rounded-xl border border-slate-200 dark:border-[#333] transition-all flex items-center gap-1.5">
                            <i data-lucide="download" class="w-4 h-4"></i> Exporter
                        </button>
                        <button wire:click="downloadTemplate" class="px-4 py-2 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-xl shadow-lg shadow-amber-500/10 transition-all flex items-center gap-1.5" title="Télécharger le modèle Excel/CSV d'appel">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Télécharger le modèle
                        </button>
                        <label class="px-4 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg shadow-emerald-500/10 transition-all flex items-center gap-1.5 cursor-pointer select-none">
                            <i data-lucide="upload" class="w-4 h-4"></i> Importer
                            <input type="file" wire:model.live="importFile" class="hidden" accept=".csv">
                        </label>
                    </div>
                </div>

                <!-- Attendance Grid -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 dark:bg-[#111]/40 border-b border-slate-100 dark:border-[#111] text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="p-4">Élève</th>
                                <th class="p-4">Matricule</th>
                                <th class="p-4 text-center">Présence</th>
                                <th class="p-4">Motif / Justification</th>
                                <th class="p-4 text-center no-print">WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#111]">
                            @forelse($studentsList as $item)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/20 transition-colors">
                                    <!-- Student profile -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            @if(!empty($item['avatar']))
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item['avatar']) }}" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-[#333]">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                                    {{ strtoupper(substr($item['first_name'], 0, 1) . substr($item['last_name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-semibold text-slate-900 dark:text-white">{{ $item['first_name'] }} {{ $item['last_name'] }}</div>
                                                <div class="text-xs text-slate-400">{{ $item['gender'] === 'M' ? 'Garçon' : 'Fille' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Matricule -->
                                    <td class="p-4 text-sm font-mono text-slate-500">{{ $item['matricule'] ?: 'N/A' }}</td>
                                    <!-- Radio inputs -->
                                    <td class="p-4">
                                        <div class="flex justify-center gap-2 max-w-xs mx-auto">
                                            <!-- Present -->
                                            <label class="relative flex-1 py-1.5 px-3 rounded-lg border cursor-pointer text-center select-none transition-all duration-200
                                                {{ $attendanceInput[$item['id']]['status'] === 'present' 
                                                    ? 'bg-emerald-500/10 border-emerald-500 text-emerald-700 dark:text-emerald-400 font-bold' 
                                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-500 dark:bg-[#0a0a0a] dark:border-[#222]' 
                                                }} flex items-center justify-center gap-1.5">
                                                <input type="radio" name="att-{{ $item['id'] }}" value="present" wire:model.live="attendanceInput.{{ $item['id'] }}.status" class="sr-only">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span class="text-xs">Présent</span>
                                            </label>
                                            <!-- Absent -->
                                            <label class="relative flex-1 py-1.5 px-3 rounded-lg border cursor-pointer text-center select-none transition-all duration-200
                                                {{ $attendanceInput[$item['id']]['status'] === 'absent' 
                                                    ? 'bg-rose-500/10 border-rose-500 text-rose-700 dark:text-rose-400 font-bold' 
                                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-500 dark:bg-[#0a0a0a] dark:border-[#222]' 
                                                }} flex items-center justify-center gap-1.5">
                                                <input type="radio" name="att-{{ $item['id'] }}" value="absent" wire:model.live="attendanceInput.{{ $item['id'] }}.status" class="sr-only">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                <span class="text-xs">Absent</span>
                                            </label>
                                            <!-- Late -->
                                            <label class="relative flex-1 py-1.5 px-3 rounded-lg border cursor-pointer text-center select-none transition-all duration-200
                                                {{ $attendanceInput[$item['id']]['status'] === 'late' 
                                                    ? 'bg-amber-500/10 border-amber-500 text-amber-700 dark:text-amber-400 font-bold' 
                                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-500 dark:bg-[#0a0a0a] dark:border-[#222]' 
                                                }} flex items-center justify-center gap-1.5">
                                                <input type="radio" name="att-{{ $item['id'] }}" value="late" wire:model.live="attendanceInput.{{ $item['id'] }}.status" class="sr-only">
                                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                <span class="text-xs">Retard</span>
                                            </label>
                                        </div>
                                    </td>
                                    <!-- Reason and is justified -->
                                    <td class="p-4">
                                        @if(in_array($attendanceInput[$item['id']]['status'], ['absent', 'late']))
                                            <div class="flex items-center gap-3">
                                                <input type="text" placeholder="Motif de l'absence..." wire:model="attendanceInput.{{ $item['id'] }}.reason" class="flex-1 py-1 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded bg-white dark:bg-[#0a0a0a] focus:ring-1 focus:ring-indigo-500 dark:text-white">
                                                
                                                <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none">
                                                    <input type="checkbox" wire:model="attendanceInput.{{ $item['id'] }}.is_justified" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-[#0a0a0a]">
                                                    Justifié
                                                </label>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium">N/A</span>
                                        @endif
                                    </td>
                                    <!-- WhatsApp Sending Status -->
                                    <td class="p-4 text-center no-print">
                                        @if(in_array($attendanceInput[$item['id']]['status'], ['absent', 'late']))
                                            <div class="flex items-center justify-center gap-2">
                                                @if(empty($item['parent_phone']))
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-500 dark:bg-[#1a1a1a] rounded-full flex items-center gap-1" title="Pas de numéro de parent renseigné">
                                                        <i data-lucide="phone-off" class="w-3 h-3"></i> Pas de N°
                                                    </span>
                                                @elseif($attendanceInput[$item['id']]['whatsapp_sent'])
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-400 rounded-full flex items-center gap-1" title="{{ $attendanceInput[$item['id']]['whatsapp_log'] }}">
                                                        <i data-lucide="check" class="w-3 h-3"></i> Envoyé
                                                    </span>
                                                    <button wire:click="sendSingleWhatsApp({{ $item['id'] }})" class="p-1 hover:bg-slate-100 dark:hover:bg-[#222] rounded text-slate-400 hover:text-indigo-600" title="Renvoyer l'alerte">
                                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                @else
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/20 dark:text-rose-400 rounded-full flex items-center gap-1" title="{{ $attendanceInput[$item['id']]['whatsapp_log'] ?: 'Non envoyé' }}">
                                                        <i data-lucide="x" class="w-3 h-3"></i> Non envoyé
                                                    </span>
                                                    <button wire:click="sendSingleWhatsApp({{ $item['id'] }})" class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 text-[10px] font-bold rounded flex items-center gap-1">
                                                        <i data-lucide="send" class="w-3 h-3"></i> Envoyer
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="users" class="w-8 h-8 text-slate-300"></i>
                                            <span>Aucun élève trouvé ou actif dans cette classe.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50/50 dark:bg-[#111]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex justify-end no-print">
                    <button wire:click="saveAttendances" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/20 font-bold transition-all flex items-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i> Enregistrer la feuille d'appel
                    </button>
                </div>
            @endif
        @endif

        <!-- TAB: GRILLE MENSUELLE -->
        @if($activeTab === 'grille_mensuelle')
            @if(empty($schoolClasses))
                <div class="p-12 text-center flex flex-col items-center justify-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-[#111] flex items-center justify-center text-slate-400 dark:text-slate-500">
                        <i data-lucide="alert-circle" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Aucune classe disponible</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm">
                        La grille mensuelle est réservée aux enseignants titulaires (responsables) de classe. Aucune classe ne vous est assignée en tant que titulaire pour cette année scolaire.
                    </p>
                </div>
            @else
            <div class="p-5 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 flex flex-wrap gap-4 items-end justify-between no-print">
                <div class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Classe</label>
                        <select wire:model.live="selectedClassId" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @foreach($schoolClasses as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Date</label>
                        <input type="date" wire:model.live="selectedDate" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Rechercher</label>
                        <div class="relative">
                            <input type="text" placeholder="Nom ou matricule..." wire:model.live="monthlySearch" class="block w-52 py-2 pl-8 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            <div class="absolute inset-y-0 left-2.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 items-center text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <!-- Legend -->
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-emerald-500/20 border border-emerald-500 flex items-center justify-center text-[10px] text-emerald-700 dark:text-emerald-400 font-bold">P</span>
                        <span>Présent</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-rose-500 border border-rose-600 flex items-center justify-center text-[10px] text-white font-bold">A</span>
                        <span>Absent</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-rose-500/10 border border-rose-400 flex items-center justify-center text-[10px] text-rose-600 font-bold">J</span>
                        <span>Justifié</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-amber-500/20 border border-amber-500 flex items-center justify-center text-[10px] text-amber-700 dark:text-amber-400 font-bold">R</span>
                        <span>Retard</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded bg-slate-100 dark:bg-[#1a1a1a] border border-slate-200 dark:border-[#333]"></span>
                        <span>Week-end</span>
                    </div>
                    
                    <button id="pdfExportBtn" onclick="exportGridToPDF()" data-class="{{ \App\Models\SchoolClass::find($selectedClassId)?->name }}" data-month="{{ \Carbon\Carbon::parse($selectedDate)->format('m_Y') }}" class="ml-4 px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold flex items-center gap-1.5 shadow-md shadow-rose-500/10 transition-all">
                        <i data-lucide="file-text" class="w-4 h-4"></i> Exporter en PDF
                    </button>
                </div>
            </div>

            <!-- Sticky First Column Grid Container -->
            <div class="overflow-x-auto max-w-full">
                <table class="w-full text-left border-collapse table-fixed min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-100/50 dark:bg-[#111]/40 border-b border-slate-200 dark:border-[#222]">
                            <!-- Student column header -->
                            <th class="p-3 text-xs font-bold text-slate-400 uppercase tracking-wider sticky left-0 bg-white dark:bg-[#0a0a0a] z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-48 border-r border-slate-200 dark:border-[#222]">
                                Élève
                            </th>
                            <!-- Days headers -->
                            @foreach($monthDaysList as $day)
                                <th class="p-1 text-center border-r border-slate-200 dark:border-[#222] min-w-[28px] max-w-[32px] {{ $day['is_weekend'] ? 'bg-slate-50 dark:bg-[#111]/30 text-slate-400' : 'text-slate-600 dark:text-slate-300' }}">
                                    <div class="text-[9px] font-bold uppercase tracking-tighter">{{ $day['day_name'] }}</div>
                                    <div class="text-xs font-bold font-space">{{ $day['day_num'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-[#222]">
                        @forelse($monthlyGridData as $studentId => $row)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/10">
                                <!-- Student Name (Sticky column) -->
                                <td class="p-3 text-xs font-bold text-slate-900 dark:text-white sticky left-0 bg-white dark:bg-[#0a0a0a] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] border-r border-slate-200 dark:border-[#222] truncate w-48">
                                    {{ $row['name'] }}
                                </td>
                                <!-- Days columns -->
                                @foreach($row['days'] as $dayNum => $dayData)
                                    <td class="p-0.5 text-center border-r border-slate-200 dark:border-[#222] min-w-[28px] max-w-[32px] {{ $dayData['is_weekend'] ? 'bg-slate-100/50 dark:bg-[#1a1a1a]/40' : '' }}">
                                        @if($dayData['status'] === 'present')
                                            <div class="w-6 h-6 mx-auto rounded bg-emerald-500/20 border border-emerald-500/40 text-emerald-800 dark:text-emerald-400 text-[10px] font-black flex items-center justify-center" title="Présent">
                                                P
                                            </div>
                                        @elseif($dayData['status'] === 'absent')
                                            @if($dayData['is_justified'])
                                                <div class="w-6 h-6 mx-auto rounded bg-rose-500/10 border border-rose-400/40 text-rose-600 dark:text-rose-400 text-[10px] font-black flex items-center justify-center" title="Absent (Justifié)">
                                                    J
                                                </div>
                                            @else
                                                <div class="w-6 h-6 mx-auto rounded bg-rose-500 text-white text-[10px] font-black flex items-center justify-center shadow-sm" title="Absent (Non Justifié)">
                                                    A
                                                </div>
                                            @endif
                                        @elseif($dayData['status'] === 'late')
                                            <div class="w-6 h-6 mx-auto rounded bg-amber-500/20 border border-amber-500/40 text-amber-800 dark:text-amber-400 text-[10px] font-black flex items-center justify-center" title="En retard">
                                                R
                                            </div>
                                        @elseif($dayData['is_weekend'])
                                            <!-- Empty weekend cell -->
                                            <div class="w-6 h-6 mx-auto"></div>
                                        @else
                                            <!-- Empty school day cell (no record) -->
                                            <div class="w-6 h-6 mx-auto border border-dashed border-slate-200 dark:border-slate-800 rounded bg-slate-50/20 dark:bg-transparent"></div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($monthDaysList) + 1 }}" class="p-8 text-center text-slate-400">
                                    Aucun élève à afficher dans la grille.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        @endif

        <!-- TAB 2: ANALYSES & RAPPORTS -->
        @if($activeTab === 'statistiques')
            <div class="p-6 space-y-8">
                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Weekly Trend ApexChart -->
                    <div class="glass-panel p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold font-space text-slate-900 dark:text-white">Évolution de l'Assiduité</h3>
                            <span class="text-xs text-slate-400 font-medium">5 dernières semaines (%)</span>
                        </div>
                        <div wire:ignore 
                             id="weeklyTrendChart" 
                             x-data="{
                                 trend: @entangle('weeklyTrendData'),
                                 chart: null,
                                 init() {
                                     const options = {
                                         series: [{
                                             name: 'Assiduité',
                                             data: this.trend.rates || []
                                         }],
                                         chart: {
                                             type: 'area',
                                             height: 250,
                                             toolbar: { show: false },
                                             zoom: { enabled: false }
                                         },
                                         colors: ['#4f46e5'],
                                         fill: {
                                             type: 'gradient',
                                             gradient: {
                                                 shadeIntensity: 1,
                                                 opacityFrom: 0.3,
                                                 opacityTo: 0.05,
                                                 stops: [0, 90, 100]
                                             }
                                         },
                                         dataLabels: { enabled: false },
                                         stroke: { curve: 'smooth', width: 3 },
                                         xaxis: {
                                             categories: this.trend.dates || [],
                                             labels: { style: { colors: '#94a3b8' } }
                                         },
                                         yaxis: {
                                             max: 100,
                                             labels: {
                                                 formatter: function (value) { return value + '%'; },
                                                 style: { colors: '#94a3b8' }
                                             }
                                         },
                                         grid: {
                                             borderColor: '#3341551a',
                                             strokeDashArray: 4
                                         }
                                     };
                                     this.chart = new ApexCharts(this.$el, options);
                                     this.chart.render();

                                     this.$watch('trend', (newTrend) => {
                                         if (newTrend) {
                                             this.chart.updateSeries([{ data: newTrend.rates || [] }]);
                                             this.chart.updateOptions({
                                                 xaxis: { categories: newTrend.dates || [] }
                                             });
                                         }
                                     }, { deep: true });
                                 }
                             }"
                             class="h-64 w-full"></div>
                    </div>

                    <!-- Class Comparison ApexChart -->
                    <div class="glass-panel p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold font-space text-slate-900 dark:text-white">Comparaison de Présence par Classe</h3>
                            <span class="text-xs text-slate-400 font-medium">Taux annuel moyen (%)</span>
                        </div>
                        <div wire:ignore 
                             id="classComparisonChart" 
                             x-data="{
                                 comp: @entangle('classComparisonData'),
                                 chart: null,
                                 init() {
                                     const options = {
                                         series: [{
                                             name: 'Assiduité Annuelle',
                                             data: this.comp.rates || []
                                         }],
                                         chart: {
                                             type: 'bar',
                                             height: 250,
                                             toolbar: { show: false }
                                         },
                                         plotOptions: {
                                             bar: {
                                                 borderRadius: 6,
                                                 columnWidth: '45%',
                                                 distributed: true,
                                             }
                                         },
                                         colors: ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
                                         dataLabels: { enabled: false },
                                         legend: { show: false },
                                         xaxis: {
                                             categories: this.comp.classes || [],
                                             labels: { style: { colors: '#94a3b8' } }
                                         },
                                         yaxis: {
                                             max: 100,
                                             labels: {
                                                 formatter: function (value) { return value + '%'; },
                                                 style: { colors: '#94a3b8' }
                                             }
                                         },
                                         grid: {
                                             borderColor: '#3341551a',
                                             strokeDashArray: 4
                                         }
                                     };
                                     this.chart = new ApexCharts(this.$el, options);
                                     this.chart.render();

                                     this.$watch('comp', (newComp) => {
                                         if (newComp) {
                                             this.chart.updateSeries([{ data: newComp.rates || [] }]);
                                             this.chart.updateOptions({
                                                 xaxis: { categories: newComp.classes || [] }
                                             });
                                         }
                                     }, { deep: true });
                                 }
                             }"
                             class="h-64 w-full"></div>
                    </div>
                </div>

                <!-- Tabular Report: Weekly/Monthly/Yearly student counts -->
                <div class="glass-panel p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">Rapport d'Assiduité des Élèves</h3>
                            <p class="text-xs text-slate-500">Statistiques calculées pour l'année scolaire et le mois en cours.</p>
                        </div>
                        
                        <div class="flex gap-3 no-print">
                            <!-- Search -->
                            <div class="relative">
                                <input type="text" placeholder="Rechercher un élève..." wire:model.live="reportSearch" class="py-1.5 pl-8 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white w-56">
                                <div class="absolute inset-y-0 left-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </div>
                            </div>
                            <!-- Print -->
                            <button onclick="window.print()" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-[#1a1a1a] dark:text-gray-300 dark:hover:bg-[#222] rounded-lg font-bold flex items-center gap-1.5">
                                <i data-lucide="printer" class="w-4 h-4"></i> Imprimer
                            </button>
                        </div>
                    </div>

                    <!-- Print Title Block -->
                    <div class="hidden print:block mb-6 text-center border-b pb-4">
                        <h2 class="text-xl font-bold">Rapport Annuel de Présence - Classe : {{ \App\Models\SchoolClass::find($selectedClassId)?->name }}</h2>
                        <p class="text-sm text-slate-500">Généré le {{ date('d/m/Y') }} | Yebook SIGS</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100/50 dark:bg-[#111]/40 border-b border-slate-100 dark:border-[#111] text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-3">Élève</th>
                                    <th class="p-3">Matricule</th>
                                    <th class="p-3 text-center">Présence (Annuel)</th>
                                    <th class="p-3 text-center text-rose-500">Absences (Annuel)</th>
                                    <th class="p-3 text-center text-amber-500">Retards (Annuel)</th>
                                    <th class="p-3 text-center text-green-500">Justifiées (Annuel)</th>
                                    <th class="p-3 text-center text-rose-400">Absences (Mensuel)</th>
                                    <th class="p-3 text-center">Taux Assiduité</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#111] text-sm">
                                @forelse($filteredStudentReport as $studentId => $row)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/10">
                                        <td class="p-3 font-semibold text-slate-900 dark:text-white">{{ $row['name'] }}</td>
                                        <td class="p-3 font-mono text-slate-500 text-xs">{{ $row['matricule'] ?: 'N/A' }}</td>
                                        <td class="p-3 text-center font-semibold text-slate-600 dark:text-slate-300">{{ $row['year_present'] }}j</td>
                                        <td class="p-3 text-center font-bold text-rose-600 dark:text-rose-400">{{ $row['year_absent'] }}j</td>
                                        <td class="p-3 text-center font-semibold text-amber-600 dark:text-amber-400">{{ $row['year_late'] }}</td>
                                        <td class="p-3 text-center font-medium text-emerald-600 dark:text-emerald-400">{{ $row['year_justified'] }}</td>
                                        <td class="p-3 text-center font-semibold text-rose-400">{{ $row['month_absent'] }}j</td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold 
                                                {{ $row['rate'] >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-950/20 dark:text-green-400' : ($row['rate'] >= 75 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/20 dark:text-amber-400' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/20 dark:text-rose-400') }}">
                                                {{ $row['rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-6 text-center text-slate-400">Aucune donnée statistique.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endif

        <!-- TAB 3: LOGS D'ALERTES -->
        @if($activeTab === 'logs')
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between border-b pb-4 dark:border-[#222]">
                    <div>
                        <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">Journal des Alertes WhatsApp</h3>
                        <p class="text-xs text-slate-500">Historique des notifications simulées envoyées aux parents de l'école.</p>
                    </div>
                    <button wire:click="clearWhatsAppLogs" class="px-3.5 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/20 dark:text-rose-400 rounded-xl text-xs font-bold flex items-center gap-1.5">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Vider l'historique
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($whatsapp_simulation_logs as $log)
                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a] flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                                <i data-lucide="message-square" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">Parent : {{ $log['phone'] }}</span>
                                    <span class="text-xs text-slate-400 font-medium">{{ \Carbon\Carbon::parse($log['date'])->format('d M Y à H:i') }}</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 italic">"{{ $log['message'] }}"</p>
                                <div class="flex items-center gap-1 text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Simulé avec succès
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="message-square-dashed" class="w-8 h-8 text-slate-300"></i>
                                <span>Aucune alerte envoyée pour le moment.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- TAB 4: CONFIGURATION WHATSAPP -->
        @if(auth()->user()->hasRole('Super Admin'))
            @if($activeTab === 'whatsapp_settings')
                <div class="p-6 max-w-4xl mx-auto space-y-6">
                    <div>
                        <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-1">Configuration de l'API WhatsApp</h3>
                        <p class="text-xs text-slate-500">Configurez votre passerelle WhatsApp (compatible Evolution API, UltraMsg, ou Webhooks JSON) ou utilisez le mode simulation.</p>
                    </div>

                    <form wire:submit.prevent="saveWhatsAppSettings" class="space-y-6">
                        
                        <!-- Enabled / Disabled Toggle -->
                        <div class="p-4 bg-slate-50 dark:bg-[#111] rounded-xl border border-slate-100 dark:border-[#1a1a1a] flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white block">Activer l'envoi réel via API</span>
                                <span class="text-xs text-slate-400 block">Si désactivé, les messages seront simulés et enregistrés dans le Journal des Alertes.</span>
                            </div>
                            <input type="checkbox" wire:model="whatsapp_api_enabled" class="checkbox checkbox-primary">
                        </div>

                        <!-- API Details -->
                        @if($whatsapp_api_enabled)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">URL de l'API WhatsApp (HTTP POST)</label>
                                    <input type="url" wire:model="whatsapp_api_url" placeholder="https://api.evolution.com/message/sendText/..." class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('whatsapp_api_url') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Token ou Clé API (apikey / Bearer)</label>
                                    <input type="text" wire:model="whatsapp_api_token" placeholder="Ex: my_secure_api_key_123" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all">
                                    @error('whatsapp_api_token') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif

                        <!-- Template Editor -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Modèle de Message d'Alerte</label>
                            <textarea wire:model="whatsapp_message_template" rows="4" class="block w-full py-2.5 px-3.5 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white transition-all font-sans"></textarea>
                            @error('whatsapp_message_template') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            
                            <!-- Placeholders documentation -->
                            <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl text-xs text-indigo-700 dark:text-indigo-400 space-y-1">
                                <span class="font-bold">Balises disponibles pour la personnalisation :</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 font-mono text-[10px] pt-1">
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{parent_name}</strong> : Nom du parent</div>
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{student_name}</strong> : Prénom + Nom de l'élève</div>
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{class_name}</strong> : Nom de la classe</div>
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{status}</strong> : absent(e) ou en retard</div>
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{date}</strong> : Date du jour d'appel</div>
                                    <div><strong class="text-indigo-900 dark:text-indigo-300">{reason}</strong> : Motif de l'absence renseigné</div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-500/20 font-bold transition-all flex items-center gap-2">
                                <i data-lucide="check" class="w-5 h-5"></i> Enregistrer les paramètres
                            </button>
                        </div>

                    </form>
                </div>
            @endif
        @endif

    </div>

    <!-- PDF Print Template (Hidden on screen) -->
    <div id="pdfPrintArea" style="display: none;" class="p-6 bg-white text-slate-900 font-sans text-xs">
        <!-- Header -->
        <div class="border-b border-slate-300 pb-4 mb-6 flex items-center justify-between">
            <div class="space-y-1">
                <div class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Yeschool | Administration</div>
                <h1 class="text-base font-extrabold text-slate-900 tracking-tight">RAPPORT MENSUEL DE PRÉSENCES</h1>
                <div class="text-xs text-slate-600 font-medium">
                    Classe : <strong class="text-slate-900">{{ \App\Models\SchoolClass::find($selectedClassId)?->name }}</strong> | 
                    Mois : <strong class="text-slate-900">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('F Y') }}</strong>
                </div>
            </div>
            <div class="text-right space-y-0.5 text-[9px] text-slate-500">
                <div>Document officiel généré le {{ date('d/m/Y') }}</div>
                <div>Année Académique : {{ \App\Models\AcademicYear::where('is_active', true)->first()?->name ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Attendance Calendar Matrix Table -->
        <table class="w-full text-left border border-slate-300 border-collapse table-fixed">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-300 text-[9px] text-slate-600">
                    <th class="p-1.5 border-r border-slate-300 font-bold w-48 text-left">Nom de l'élève</th>
                    @foreach($monthDaysList as $day)
                        <th class="p-0.5 border-r border-slate-300 text-center font-bold text-[8px] min-w-[20px] {{ $day['is_weekend'] ? 'bg-slate-50 text-slate-400' : '' }}">
                            <div>{{ $day['day_name'] }}</div>
                            <div class="text-[9px]">{{ $day['day_num'] }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($monthlyGridData as $studentId => $row)
                    <tr class="border-b border-slate-200">
                        <td class="p-1.5 border-r border-slate-300 font-bold truncate text-[9px] text-slate-900 bg-white">
                            {{ $row['name'] }}
                        </td>
                        @foreach($row['days'] as $dayNum => $dayData)
                            <td class="p-0 border-r border-slate-200 text-center text-[9px] {{ $dayData['is_weekend'] ? 'bg-slate-50' : '' }}">
                                @if($dayData['status'] === 'present')
                                    <span class="text-emerald-600 font-bold text-[8px]">P</span>
                                @elseif($dayData['status'] === 'absent')
                                    @if($dayData['is_justified'])
                                        <span class="text-rose-400 font-bold text-[8px]">J</span>
                                    @else
                                        <span class="text-rose-600 font-bold text-[8px]">A</span>
                                    @endif
                                @elseif($dayData['status'] === 'late')
                                    <span class="text-amber-600 font-bold text-[8px]">R</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Legend & Footer -->
        <div class="mt-8 flex justify-between items-start">
            <!-- Legend -->
            <div class="space-y-1 text-[8px] text-slate-500 border border-slate-200 p-2.5 rounded-lg bg-slate-50/50">
                <div class="font-bold text-slate-700 uppercase tracking-wider mb-1">Légende :</div>
                <div class="flex items-center gap-3">
                    <div><strong class="text-emerald-600">P</strong> : Présent</div>
                    <div><strong class="text-rose-600">A</strong> : Absent</div>
                    <div><strong class="text-rose-400">J</strong> : Justifié</div>
                    <div><strong class="text-amber-600">R</strong> : Retard</div>
                </div>
            </div>

            <!-- Signature block -->
            <div class="text-center w-64 space-y-10">
                <div class="text-[9px] font-bold text-slate-700">Le Directeur de l'Établissement</div>
                <div class="border-b border-dashed border-slate-400 w-40 mx-auto"></div>
                <div class="text-[8px] text-slate-400">Signature & Cachet</div>
            </div>
        </div>
    </div>

    <!-- PDF Generation Script with Dynamic Loader and DOM offscreen capture -->
    <script>
        function exportGridToPDF() {
            const btn = document.querySelector("#pdfExportBtn");
            const originalText = btn.innerHTML;
            btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Chargement...`;
            if (window.lucide) {
                lucide.createIcons();
            }

            // Dynamic loader for html2pdf.js CDN
            if (typeof html2pdf === "undefined") {
                const script = document.createElement("script");
                script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
                script.onload = () => {
                    generatePDF(btn, originalText);
                };
                script.onerror = () => {
                    alert("Impossible de charger le module PDF. Veuillez vérifier votre connexion Internet.");
                    btn.innerHTML = originalText;
                    if (window.lucide) lucide.createIcons();
                };
                document.head.appendChild(script);
            } else {
                generatePDF(btn, originalText);
            }
        }

        function generatePDF(btn, originalText) {
            btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Génération PDF...`;
            if (window.lucide) {
                lucide.createIcons();
            }

            // Clone print element and append to DOM offscreen so html2canvas can render it
            const printEl = document.querySelector("#pdfPrintArea").cloneNode(true);
            printEl.style.display = "block";
            printEl.style.position = "absolute";
            printEl.style.left = "-9999px";
            printEl.style.top = "-9999px";
            document.body.appendChild(printEl);

            const className = btn.getAttribute('data-class') || 'Classe';
            const monthStr = btn.getAttribute('data-month') || 'Mois';
            const filename = 'Rapport_Presences_' + className.replace(/\s+/g, '_') + '_' + monthStr + '.pdf';

            const opt = {
                margin:       [8, 8, 8, 8],
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2.5, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(printEl).save().then(() => {
                document.body.removeChild(printEl);
                btn.innerHTML = originalText;
                if (window.lucide) {
                    lucide.createIcons();
                }
            }).catch(err => {
                console.error("PDF generation failed:", err);
                if (printEl.parentNode) {
                    document.body.removeChild(printEl);
                }
                btn.innerHTML = originalText;
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        }
    </script>

</div>
