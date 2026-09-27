<div class="h-full flex flex-col p-6 max-w-7xl mx-auto">

    <!-- Styles for print output -->
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 8mm 10mm 8mm;
            }
            html, body {
                background: white !important;
                color: black !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            /* Hide ALL default navigation / dashboard frames in layout */
            aside, header, footer, nav, .no-print, [role="navigation"], button {
                display: none !important;
                height: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
            }
            /* Reset wrapper flex constraints */
            .flex.h-screen {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
            }
            main, .grow, .p-6, .glass-panel {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                background: transparent !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
            }
            /* Position the RDC bulletin sheet absolute at the very top of printed page */
            #rdc-bulletin-sheet {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border: 2px solid black !important;
                box-shadow: none !important;
                padding: 15px !important;
                margin: 0 !important;
                background: white !important;
                visibility: visible !important;
                z-index: 99999 !important;
            }
            #rdc-bulletin-sheet * {
                visibility: visible !important;
            }
            /* Enforce Table layout fitting 100% of A4 portrait */
            #rdc-bulletin-sheet table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
            }
            #rdc-bulletin-sheet th, #rdc-bulletin-sheet td {
                padding: 3px 2px !important;
                font-size: 8px !important;
                border: 1px solid black !important;
                word-wrap: break-word !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            /* Adjust title and margins to fit on one page */
            .mb-6 { margin-bottom: 0.5rem !important; }
            .mt-6 { margin-top: 0.5rem !important; }
            .mt-8 { margin-top: 0.75rem !important; }
            .p-4 { padding: 0.5rem !important; }
            .p-6 { padding: 0.75rem !important; }
            .p-8 { padding: 1rem !important; }
            .gap-4 { gap: 0.5rem !important; }
        }
    </style>

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8 no-print">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notes & Bulletins</h1>
            <p class="text-slate-500 text-sm mt-1">Saisissez les notes d'évaluation par classe et matière, ou générez et imprimez les bulletins de notes.</p>
        </div>
    </div>

    <!-- TABS -->
    <div class="flex gap-2 border-b border-slate-200 dark:border-[#333] no-print">
        <button wire:click="switchTab('saisie')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'saisie' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
            <i data-lucide="edit-3" class="w-4 h-4"></i>
            Saisie des notes
        </button>
        <button wire:click="switchTab('bulletins')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'bulletins' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            Bulletins (RDC National)
        </button>
        <button wire:click="switchTab('classroom')" 
                class="px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2
                {{ $activeTab === 'classroom' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' }}">
            <i data-lucide="presentation" class="w-4 h-4"></i>
            Composition (Classroom)
        </button>
    </div>

    <!-- FLASH MESSAGE -->
    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-[#333] text-emerald-700 dark:text-emerald-400 rounded-lg text-sm flex items-center gap-3 no-print">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex-1 bg-white dark:bg-[#0a0a0a] rounded-2xl border border-slate-200 dark:border-[#333] shadow-sm overflow-hidden flex flex-col min-h-[500px]">
        
        @if($activeTab === 'saisie')
            <!-- TAB: SAISIE DES NOTES -->
            <div class="p-5 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/30 flex flex-wrap gap-4 items-end no-print">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Classe</label>
                    <select wire:model.live="selectedClassId" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        @foreach($schoolClasses as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Matière</label>
                    <select wire:model.live="selectedSubjectId" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        @foreach($subjects as $sub)
                            <option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Période</label>
                    <select wire:model.live="selectedTerm" class="block w-48 py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        <option value="1ère Période">1ère Période</option>
                        <option value="2ème Période">2ème Période</option>
                        <option value="Examen 1er Semestre">Examen 1er Semestre</option>
                        <option value="3ème Période">3ème Période</option>
                        <option value="4ème Période">4ème Période</option>
                        <option value="Examen 2ème Semestre">Examen 2ème Semestre</option>
                    </select>
                </div>
                <div class="ml-auto">
                    <button type="button" wire:click="saveGrades" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-500/20 flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Enregistrer
                        <div wire:loading wire:target="saveGrades" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto relative min-h-[300px]">
                <div wire:loading.delay wire:target="selectedClassId, selectedSubjectId, selectedTerm" class="absolute inset-0 bg-white/50 dark:bg-[#0a0a0a]/50 z-10 backdrop-blur-sm flex items-center justify-center">
                    <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
                </div>
                
                @if(empty($studentsList))
                    <div class="flex flex-col items-center justify-center h-48 text-slate-500 dark:text-slate-400">
                        <i data-lucide="users" class="w-8 h-8 mb-2 opacity-50"></i>
                        <p>Aucun élève dans cette classe.</p>
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-100 dark:border-[#1a1a1a]">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Élève</th>
                                <th class="px-6 py-4 font-semibold w-48">Note (sur {{ $currentMax }})</th>
                                <th class="px-6 py-4 font-semibold">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                            @foreach($studentsList as $student)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/50">
                                    <td class="px-6 py-3 font-medium text-slate-900 dark:text-white">
                                        {{ $student['last_name'] }} {{ $student['first_name'] }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="number" step="0.25" min="0" max="{{ $currentMax }}" 
                                               wire:model="gradesInput.{{ $student['id'] }}.score" 
                                               class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-md bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white font-mono">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="text" placeholder="Commentaire optionnel..."
                                               wire:model="gradesInput.{{ $student['id'] }}.comments" 
                                               class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-md bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            
        @elseif($activeTab === 'bulletins')
            <!-- TAB: BULLETINS -->
            <div class="flex flex-col lg:flex-row min-h-[600px]">
                
                <!-- Sidebar: Student List -->
                <div class="w-full lg:w-72 border-b lg:border-b-0 lg:border-r border-slate-100 dark:border-[#1a1a1a] bg-slate-50/30 dark:bg-[#111]/30 flex flex-col no-print">
                    <div class="p-4 border-b border-slate-100 dark:border-[#1a1a1a] space-y-3">
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Sélectionner Classe</label>
                        <select wire:model.live="selectedClassId" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @foreach($schoolClasses as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto">
                        @if(empty($studentsList))
                            <div class="p-6 text-center text-slate-500 text-sm">Aucun élève trouvé.</div>
                        @else
                            <ul class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                                @foreach($studentsList as $student)
                                    <li>
                                        <button wire:click="$set('selectedStudentId', {{ $student['id'] }})" class="w-full text-left px-4 py-3 text-sm transition-colors {{ $selectedStudentId == $student['id'] ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-semibold border-l-2 border-indigo-600' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#111] border-l-2 border-transparent' }}">
                                            {{ $student['last_name'] }} {{ $student['first_name'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <!-- Bulletin View -->
                <div class="flex-1 bg-slate-100 dark:bg-[#0a0a0a] p-4 lg:p-8 flex flex-col items-center overflow-y-auto relative">
                    <div wire:loading.delay wire:target="selectedStudentId" class="absolute inset-0 bg-white/50 dark:bg-[#0a0a0a]/50 z-10 backdrop-blur-sm flex items-center justify-center no-print">
                        <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
                    </div>

                    @if(empty($bulletinData) || empty($bulletinData['student']))
                        <div class="flex flex-col items-center justify-center h-full text-slate-500 py-12 no-print">
                            <i data-lucide="file-text" class="w-12 h-12 mb-4 opacity-30"></i>
                            <p>Sélectionnez un élève pour voir son bulletin scolaire RDC.</p>
                        </div>
                    @else
                        <!-- RDC Print Toolbar -->
                        <div class="w-full max-w-5xl mb-4 flex justify-between items-center no-print">
                            <span class="text-xs text-slate-500 font-medium">Aperçu officiel du bulletin national (Format RDC)</span>
                            <button onclick="window.print();" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-semibold rounded-lg hover:bg-slate-800 dark:hover:bg-slate-100 transition-colors flex items-center gap-2 shadow">
                                <i data-lucide="printer" class="w-4 h-4"></i> Imprimer le bulletin
                            </button>
                        </div>

                        <!-- Paper sheet simulation matching RDC Standard Report Card -->
                        <div id="rdc-bulletin-sheet" class="w-full max-w-5xl bg-white text-black p-6 md:p-10 shadow-2xl rounded-xl border border-slate-300 flex flex-col font-sans">
                            
                            <!-- RDC Republic Header -->
                            <div class="border-2 border-black p-4 mb-6">
                                <div class="flex items-center justify-between gap-4">
                                    <!-- DRC Flag SVG -->
                                    <div class="flex-shrink-0">
                                        <svg class="w-20 h-14 border border-black" viewBox="0 0 800 600" xmlns="http://www.w3.org/2000/svg">
                                            <rect width="800" height="600" fill="#007FFF"/>
                                            <path d="M 0 600 L 800 0 L 800 90 L 120 600 Z" fill="#F8D147"/>
                                            <path d="M 0 600 L 800 0 L 800 60 L 80 600 Z" fill="#D21034"/>
                                            <polygon points="120,60 140,120 200,120 150,160 170,220 120,180 70,220 90,160 40,120 100,120" fill="#F8D147" transform="scale(1.2) translate(-20, -10)"/>
                                        </svg>
                                    </div>
                                    
                                    <!-- Ministry Text -->
                                    <div class="text-center flex-1">
                                        <h2 class="text-lg font-bold uppercase tracking-wide leading-tight">République Démocratique du Congo</h2>
                                        <h3 class="text-sm font-bold uppercase mt-1 leading-tight">Ministère de l'Enseignement Primaire, Secondaire et Technique</h3>
                                        <div class="text-[10px] font-mono mt-1 border-t border-black pt-1 inline-block px-4 uppercase font-bold text-indigo-900">{{ $bulletinData['school_name'] }}</div>
                                    </div>
                                    
                                    <!-- Photo slot / Avatar -->
                                    <div class="w-20 h-24 border-2 border-black flex items-center justify-center text-center text-[10px] font-bold bg-slate-50 uppercase overflow-hidden">
                                        @if(!empty($bulletinData['student']->avatar))
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($bulletinData['student']->avatar) }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="p-1">Photo Élève</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Identification ID bar -->
                            <div class="flex items-center gap-2 mb-6 border border-black p-2 text-xs">
                                <span class="font-bold whitespace-nowrap">N° ID. :</span>
                                <div class="flex gap-1 flex-1">
                                    @php
                                        $idStr = $bulletinData['student']->matricule ?? '12' . str_pad($bulletinData['student']->id, 6, '0', STR_PAD_LEFT) . str_repeat('-', 20);
                                        $idChars = str_split(str_replace(' ', '-', $idStr));
                                    @endphp
                                    @foreach($idChars as $char)
                                        <div class="w-5 h-6 border border-black flex items-center justify-center font-mono font-bold text-sm bg-slate-50">
                                            {{ $char }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Dual column Metadata details -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border border-black p-4 mb-6 text-xs leading-relaxed">
                                <div class="space-y-1">
                                    <p><span class="font-bold">PROVINCE :</span> <span class="border-b border-dotted border-black inline-block min-w-[200px] uppercase font-bold">{{ $bulletinData['school_province'] }}</span></p>
                                    <p><span class="font-bold">VILLE :</span> <span class="border-b border-dotted border-black inline-block min-w-[200px] uppercase font-bold">{{ $bulletinData['school_ville'] }}</span></p>
                                    <p><span class="font-bold">COMMUNE / TER. :</span> <span class="border-b border-dotted border-black inline-block min-w-[200px] uppercase font-bold">{{ $bulletinData['school_commune'] }}</span></p>
                                    <p><span class="font-bold">ECOLE :</span> <span class="font-bold uppercase text-indigo-900">{{ $bulletinData['school_name'] }}</span></p>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold">CODE ECOLE :</span>
                                        <div class="flex gap-0.5">
                                            @foreach(str_split(str_pad($bulletinData['school_code_ecole'], 6, '0', STR_PAD_LEFT)) as $char)
                                                <span class="w-4 h-5 border border-black flex items-center justify-center font-mono font-bold">{{ $char }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1 md:border-l md:border-black md:pl-4">
                                    <p><span class="font-bold">ELEVE :</span> <span class="font-bold text-sm uppercase">{{ $bulletinData['student']->first_name }} {{ $bulletinData['student']->last_name }}</span></p>
                                    <p><span class="font-bold">SEXE :</span> <span class="border-b border-dotted border-black inline-block min-w-[50px] uppercase font-bold">{{ $bulletinData['student']->gender === 'M' ? 'MASCULIN' : 'FEMININ' }}</span></p>
                                    <p><span class="font-bold">NE(E) A :</span> <span class="border-b border-dotted border-black inline-block min-w-[120px]">Kinshasa</span> <span class="font-bold">LE :</span> <span class="border-b border-dotted border-black inline-block min-w-[100px]">{{ $bulletinData['student']->date_of_birth ?? '12/10/2012' }}</span></p>
                                    <p><span class="font-bold">CLASSE :</span> <span class="font-bold text-indigo-700">{{ $bulletinData['student']->schoolClass?->name ?? 'N/A' }}</span></p>
                                    <p><span class="font-bold">MATRICULE :</span> <span class="font-mono">#STD-{{ str_pad($bulletinData['student']->id, 4, '0', STR_PAD_LEFT) }}</span></p>
                                </div>
                            </div>

                            <!-- Title banner -->
                            <div class="text-center py-2 border-y-2 border-black mb-6">
                                <h2 class="text-md font-bold uppercase tracking-wider">
                                    BULLETIN DE L'ELEVE &mdash; ANNEE SCOLAIRE {{ $bulletinData['student']->schoolClass?->academic_year ?? '2026-2027' }}
                                </h2>
                            </div>

                            <!-- BULLETIN MAIN GRID -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-left border-collapse border-2 border-black">
                                    <thead>
                                        <!-- Level 1 Headers -->
                                        <tr class="bg-slate-100 text-black border-b-2 border-black">
                                            <th rowspan="2" class="px-2 py-3 border border-black font-bold uppercase w-[220px]">Branches (Matières)</th>
                                            <th rowspan="2" class="px-1 py-1 border border-black font-bold text-center w-[50px]">Max Période</th>
                                            <th colspan="4" class="px-2 py-1 border border-black font-bold text-center uppercase">Premier Semestre</th>
                                            <th colspan="4" class="px-2 py-1 border border-black font-bold text-center uppercase">Second Semestre</th>
                                            <th colspan="2" class="px-2 py-1 border border-black font-bold text-center uppercase bg-emerald-50">Total</th>
                                        </tr>
                                        <!-- Level 2 Headers -->
                                        <tr class="bg-slate-50 text-black border-b-2 border-black text-[10px]">
                                            <!-- Sem 1 -->
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[40px]">1ère P</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[40px]">2ème P</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[50px] bg-slate-100">Examen</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[55px] bg-indigo-50">Total</th>
                                            <!-- Sem 2 -->
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[40px]">3ème P</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[40px]">4ème P</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[50px] bg-slate-100">Examen</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[55px] bg-indigo-50">Total</th>
                                            <!-- Total general -->
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[50px] bg-emerald-50">Max TG</th>
                                            <th class="px-1 py-1 border border-black font-bold text-center w-[60px] bg-emerald-100">Obtenu</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-black">
                                        @foreach($bulletinData['lines'] as $line)
                                            <tr class="hover:bg-slate-50/50">
                                                <!-- Branch Name -->
                                                <td class="px-2 py-2 border border-black font-semibold uppercase" style="color: {{ $line['color'] }}">
                                                    {{ $line['subject'] }}
                                                </td>
                                                
                                                <!-- Max Period -->
                                                <td class="px-1 py-2 border border-black text-center font-bold font-mono">
                                                    {{ $line['max_p'] }}
                                                </td>

                                                <!-- Sem 1 scores -->
                                                <td class="px-1 py-2 border border-black text-center font-mono">
                                                    {{ $line['p1'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-mono">
                                                    {{ $line['p2'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50 font-semibold">
                                                    {{ $line['ex1'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-bold font-mono bg-indigo-50/40">
                                                    {{ $line['tot1'] ?? '-' }}
                                                </td>

                                                <!-- Sem 2 scores -->
                                                <td class="px-1 py-2 border border-black text-center font-mono">
                                                    {{ $line['p3'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-mono">
                                                    {{ $line['p4'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50 font-semibold">
                                                    {{ $line['ex2'] ?? '-' }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-bold font-mono bg-indigo-50/40">
                                                    {{ $line['tot2'] ?? '-' }}
                                                </td>

                                                <!-- Total Général -->
                                                <td class="px-1 py-2 border border-black text-center font-bold font-mono bg-emerald-50">
                                                    {{ $line['max_tg'] }}
                                                </td>
                                                <td class="px-1 py-2 border border-black text-center font-bold font-mono bg-emerald-100 text-indigo-900">
                                                    {{ $line['tg'] ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach

                                        <!-- METRICS BLOCK: MAXIMA GENERAUX -->
                                        <tr class="bg-slate-100 font-bold border-t-2 border-black">
                                            <td class="px-2 py-2 border border-black uppercase text-right">Maxima Généraux</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <!-- Sem 1 Maxima totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colMaxTotals']['1P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colMaxTotals']['2P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-200">{{ $bulletinData['colMaxTotals']['Ex1'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-100">{{ $bulletinData['colMaxTotals']['Tot1'] }}</td>
                                            <!-- Sem 2 Maxima totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colMaxTotals']['3P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colMaxTotals']['4P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-200">{{ $bulletinData['colMaxTotals']['Ex2'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-100">{{ $bulletinData['colMaxTotals']['Tot2'] }}</td>
                                            <!-- Year General Totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-100">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-200 text-indigo-900">{{ $bulletinData['colMaxTotals']['TG'] }}</td>
                                        </tr>

                                        <!-- METRICS BLOCK: TOTAUX OBTENUS -->
                                        <tr class="font-bold bg-white">
                                            <td class="px-2 py-2 border border-black uppercase text-right">Totaux Obtenus</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <!-- Sem 1 obtained scores -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colStudentTotals']['1P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colStudentTotals']['2P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50">{{ $bulletinData['colStudentTotals']['Ex1'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50">{{ $bulletinData['colStudentTotals']['Tot1'] }}</td>
                                            <!-- Sem 2 obtained scores -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colStudentTotals']['3P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colStudentTotals']['4P'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50">{{ $bulletinData['colStudentTotals']['Ex2'] }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50">{{ $bulletinData['colStudentTotals']['Tot2'] }}</td>
                                            <!-- Year General Totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-50">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-100 text-indigo-900">{{ $bulletinData['colStudentTotals']['TG'] }}</td>
                                        </tr>

                                        <!-- METRICS BLOCK: POURCENTAGE -->
                                        <tr class="font-bold bg-slate-50 text-indigo-900">
                                            <td class="px-2 py-2 border border-black uppercase text-right">Pourcentage</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <!-- Sem 1 obtained scores -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colPercentages']['1P'] ? $bulletinData['colPercentages']['1P'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colPercentages']['2P'] ? $bulletinData['colPercentages']['2P'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-100">{{ $bulletinData['colPercentages']['Ex1'] ? $bulletinData['colPercentages']['Ex1'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-100/50">{{ $bulletinData['colPercentages']['Tot1'] ? $bulletinData['colPercentages']['Tot1'] . ' %' : '-' }}</td>
                                            <!-- Sem 2 obtained scores -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colPercentages']['3P'] ? $bulletinData['colPercentages']['3P'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colPercentages']['4P'] ? $bulletinData['colPercentages']['4P'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-100">{{ $bulletinData['colPercentages']['Ex2'] ? $bulletinData['colPercentages']['Ex2'] . ' %' : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-100/50">{{ $bulletinData['colPercentages']['Tot2'] ? $bulletinData['colPercentages']['Tot2'] . ' %' : '-' }}</td>
                                            <!-- Year General Totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-50">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-200 font-bold text-sm">{{ $bulletinData['colPercentages']['TG'] ? $bulletinData['colPercentages']['TG'] . ' %' : '-' }}</td>
                                        </tr>

                                        <!-- METRICS BLOCK: CLASSIFICATION RANK / PLACE -->
                                        <tr class="font-bold bg-white text-slate-800 text-[10px]">
                                            <td class="px-2 py-2 border border-black uppercase text-right">Place / Nbre Elèves</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <!-- Sem 1 Place -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colRanks']['1P'] !== '-' ? $bulletinData['colRanks']['1P'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colRanks']['2P'] !== '-' ? $bulletinData['colRanks']['2P'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50">{{ $bulletinData['colRanks']['Ex1'] !== '-' ? $bulletinData['colRanks']['Ex1'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/50">{{ $bulletinData['colRanks']['Tot1'] !== '-' ? $bulletinData['colRanks']['Tot1'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <!-- Sem 2 Place -->
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colRanks']['3P'] !== '-' ? $bulletinData['colRanks']['3P'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">{{ $bulletinData['colRanks']['4P'] !== '-' ? $bulletinData['colRanks']['4P'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50">{{ $bulletinData['colRanks']['Ex2'] !== '-' ? $bulletinData['colRanks']['Ex2'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/50">{{ $bulletinData['colRanks']['Tot2'] !== '-' ? $bulletinData['colRanks']['Tot2'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                            <!-- Year General Totals -->
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-50">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-100 text-indigo-900">{{ $bulletinData['colRanks']['TG'] !== '-' ? $bulletinData['colRanks']['TG'] . '/' . $bulletinData['studentCount'] : '-' }}</td>
                                        </tr>

                                        <!-- METRICS BLOCK: APPLICATION (MANUAL CELLS) -->
                                        <tr class="bg-white">
                                            <td class="px-2 py-2 border border-black uppercase text-right font-semibold">Application</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/40"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/40"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-100"></td>
                                        </tr>

                                        <!-- METRICS BLOCK: CONDUITE (MANUAL CELLS) -->
                                        <tr class="bg-white">
                                            <td class="px-2 py-2 border border-black uppercase text-right font-semibold">Conduite</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono">-</td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/40"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-slate-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-indigo-50/40"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-50"></td>
                                            <td class="px-1 py-2 border border-black text-center font-mono bg-emerald-100"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- RDC Decisions Block at bottom -->
                            <div class="border-2 border-black p-4 mt-6 text-xs space-y-2">
                                <h4 class="font-bold uppercase border-b border-black pb-1 mb-2">Décisions du Conseil de Classe</h4>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 border border-black rounded flex items-center justify-center font-bold text-sm bg-slate-100">
                                        @if(($bulletinData['colPercentages']['TG'] ?? 0.0) >= 50.0)
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-black"></i>
                                        @endif
                                    </div>
                                    <span>L'élève passe dans la classe supérieure.</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 border border-black rounded flex items-center justify-center font-bold text-sm bg-slate-100">
                                        @if(($bulletinData['colPercentages']['TG'] ?? 0.0) < 50.0 && ($bulletinData['colPercentages']['TG'] ?? null) !== null)
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-black"></i>
                                        @endif
                                    </div>
                                    <span>L'élève double la classe.</span>
                                </div>
                                <div class="pt-2 italic text-[10px] border-t border-black text-slate-600">
                                    * Note importante : Le bulletin est sans valeur s'il est raturé ou surchargé. Interdiction formelle de reproduire ce bulletin sous peine de sanctions prévues par la loi.
                                </div>
                            </div>

                            <!-- RDC Signatures block -->
                            <div class="grid grid-cols-3 gap-4 mt-8 text-center text-xs">
                                <div>
                                    <p class="font-bold underline">Signature de l'élève</p>
                                    <div class="h-16 flex items-center justify-center text-[10px] text-slate-400 italic">Signature libre</div>
                                </div>
                                <div>
                                    <p class="font-bold underline">Sceau de l'école</p>
                                    <div class="h-16 flex items-center justify-center text-[10px] text-slate-400 italic">[Cachet Sec sec]</div>
                                </div>
                                <div>
                                    <p class="font-bold underline">Le Chef d'Établissement</p>
                                    <div class="h-16 flex flex-col justify-end text-[10px] text-slate-900 font-bold uppercase">
                                        <span>Fait à Kinshasa, le {{ date('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endif
                </div>
            </div>
        @elseif($activeTab === 'classroom')
            <div class="flex-1 flex flex-col h-full overflow-hidden p-6 space-y-6">
                <!-- If NO assessment is selected: Show assessments list -->
                @if(!$selectedAssessmentId)
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-white">Composition de devoirs & examens</h3>
                            <p class="text-xs text-slate-500">Gérez les devoirs, travaux pratiques et examens de la classe et matière sélectionnée.</p>
                        </div>
                        <button wire:click="openAssessmentModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-md shadow-indigo-500/10">
                            <i data-lucide="plus" class="w-4 h-4"></i> Créer une évaluation
                        </button>
                    </div>

                    <!-- Filter Information banner -->
                    <div class="p-3 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#333] rounded-xl flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-500">Classe :</span>
                            <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 font-bold rounded">
                                {{ \App\Models\SchoolClass::find($selectedClassId)?->name ?? 'N/A' }}
                            </span>
                            <span class="font-bold text-slate-500 ml-3">Matière :</span>
                            <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 font-bold rounded">
                                {{ \App\Models\Subject::find($selectedSubjectId)?->name ?? 'N/A' }}
                            </span>
                        </div>
                        <span class="text-slate-400 font-medium">Filtre actif par enseignant titulaire</span>
                    </div>

                    <!-- Table of assessments -->
                    <div class="border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333]">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Titre</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Note Max</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date Limite</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Questions</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Copies / Rendus</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#222]">
                                @forelse($classroomAssessments as $ass)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                        <td class="py-3 px-4">
                                            @if($ass['type'] === 'Devoir')
                                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-950/20 text-blue-800 dark:text-blue-400 text-[10px] font-bold rounded">Devoir</span>
                                            @elseif($ass['type'] === 'Travail')
                                                <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 text-[10px] font-bold rounded">T.P. / Travail</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-rose-100 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 text-[10px] font-bold rounded">Examen</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm font-semibold text-slate-800 dark:text-white">
                                            {{ $ass['title'] }}
                                        </td>
                                        <td class="py-3 px-4 text-sm font-bold text-center text-slate-700 dark:text-slate-300">
                                            {{ $ass['max_score'] }} pts
                                        </td>
                                        <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                            {{ $ass['due_date'] ? \Carbon\Carbon::parse($ass['due_date'])->format('d/m/Y H:i') : 'Aucune' }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-center font-bold text-slate-600 dark:text-slate-400">
                                            {{ count($ass['questions'] ?: []) }} Q.
                                        </td>
                                        <td class="py-3 px-4 text-xs text-center">
                                            @php
                                                $totalSub = \App\Models\ClassroomSubmission::where('classroom_assessment_id', $ass['id'])->count();
                                                $corrected = \App\Models\ClassroomSubmission::where('classroom_assessment_id', $ass['id'])->where('status', 'Corrigé')->count();
                                            @endphp
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded font-bold">
                                                {{ $corrected }} / {{ $totalSub }} Évalués
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="openComposeQuestionsModal({{ $ass['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Composer Questions">
                                                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                                                </button>
                                                <button wire:click="viewAssessmentSubmissions({{ $ass['id'] }})" class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Voir les Copies">
                                                    <i data-lucide="folder-open" class="w-4 h-4"></i>
                                                </button>
                                                <button wire:click="openAssessmentModal({{ $ass['id'] }})" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Modifier Paramètres">
                                                    <i data-lucide="settings" class="w-4 h-4"></i>
                                                </button>
                                                <button wire:click="deleteAssessment({{ $ass['id'] }})" onclick="confirm('Supprimer cette évaluation ?') || event.stopImmediatePropagation()" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/20" title="Supprimer">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-400 text-sm">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i data-lucide="clipboard-list" class="w-10 h-10 text-slate-300"></i>
                                                <span>Aucun devoir ou examen créé pour ce cours.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                <!-- If an assessment is selected: Show copies list -->
                @else
                    @php
                        $assessmentObj = \App\Models\ClassroomAssessment::find($selectedAssessmentId);
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button wire:click="$set('selectedAssessmentId', null)" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-[#222]">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            </button>
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-white">Retour aux devoirs</h3>
                                <p class="text-xs text-slate-500">Copie des élèves pour : <strong>{{ $assessmentObj?->title }}</strong> (Note max : {{ $assessmentObj?->max_score }} pts)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions Table -->
                    <div class="border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333]">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nom de l'élève</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Score</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Commentaire Enseignant</th>
                                    <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-[#222]">
                                @forelse($activeSubmissions as $sub)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                        <td class="py-3 px-4 font-semibold text-slate-800 dark:text-white">
                                            {{ $sub['student']['first_name'] }} {{ $sub['student']['last_name'] }}
                                            <div class="text-[9px] font-mono text-slate-400 mt-0.5">Matricule: {{ $sub['student']['matricule'] ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($sub['status'] === 'Corrigé')
                                                <span class="px-2.5 py-0.5 bg-emerald-100 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 text-[10px] font-bold rounded-full">Corrigé</span>
                                            @elseif($sub['status'] === 'À corriger')
                                                <span class="px-2.5 py-0.5 bg-amber-100 dark:bg-amber-950/20 text-amber-800 dark:text-amber-400 text-[10px] font-bold rounded-full">À corriger</span>
                                            @else
                                                <span class="px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 text-[10px] font-bold rounded-full">Non remis</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-sm font-bold text-center text-slate-700 dark:text-slate-300">
                                            @if($sub['score'] !== null)
                                                <span class="text-indigo-600 font-black">{{ $sub['score'] }}</span> / {{ $assessmentObj?->max_score }}
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                            {{ $sub['teacher_comments'] ?: '-' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="openGradingModal({{ $sub['id'] }})" class="px-2.5 py-1 bg-white hover:bg-slate-50 dark:bg-[#0a0a0a] dark:hover:bg-[#111] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#333] rounded-lg text-[10px] font-bold inline-flex items-center gap-1 transition-all">
                                                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-indigo-500"></i> Corriger
                                                </button>
                                                
                                                <button wire:click="exportSubmissionPdf({{ $sub['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Télécharger PDF">
                                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                                </button>
                                                
                                                <button wire:click="transmitToParent({{ $sub['id'] }}, 'whatsapp')" class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Transmettre par WhatsApp">
                                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                                </button>

                                                <button wire:click="transmitToParent({{ $sub['id'] }}, 'email')" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 dark:hover:bg-[#222]" title="Transmettre par Email">
                                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                            Aucun élève trouvé dans cette classe.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

    </div>

    <!-- MODAL: ASSESSMENT PARAMETERS -->
    <div x-data="{ open: @entangle('isAssessmentModalOpen') }"
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
                    <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-500"></i> Paramètres de l'évaluation
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 font-space">Titre de l'évaluation</label>
                    <input type="text" wire:model="assessment_title" placeholder="Ex: Devoir de Mathématiques 1" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 font-space">Type</label>
                        <select wire:model="assessment_type" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            <option value="Devoir">Devoir</option>
                            <option value="Travail">Travail Pratique (T.P.)</option>
                            <option value="Examen">Examen</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 font-space">Note Maximale</label>
                        <input type="number" step="0.5" wire:model="assessment_max_score" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 font-space">Date & Heure limite de remise</label>
                    <input type="datetime-local" wire:model="assessment_due_date" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5 font-space">Instructions / Consignes</label>
                    <textarea wire:model="assessment_instructions" rows="3" placeholder="Saisissez les instructions pour les élèves..." class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="saveAssessment" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: COMPOSE QUESTIONS -->
    <div x-data="{ open: @entangle('isComposeQuestionsModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-2xl bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in"
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
                    <i data-lucide="help-circle" class="w-4 h-4 text-indigo-500"></i> Composer les questions du Devoir
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100 dark:divide-[#1a1a1a] max-h-[60vh] overflow-y-auto">
                <!-- Left side: Questions List -->
                <div class="p-6 space-y-4 overflow-y-auto">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Questions ajoutées ({{ count($assessment_questions) }})</h3>
                    <div class="space-y-3">
                        @forelse($assessment_questions as $idx => $q)
                            <div class="p-3 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#222] rounded-xl relative group">
                                <button wire:click="removeQuestion({{ $idx }})" class="absolute top-2 right-2 p-1 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-[#222] transition-all">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase mb-1">
                                    Q{{ $idx + 1 }} • @if($q['type'] === 'mcq') Choix multiple @elseif($q['type'] === 'text') Texte @else Dessin @endif
                                </div>
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 pr-6">{{ $q['question'] }}</p>
                                @if($q['type'] === 'mcq')
                                    <div class="mt-1.5 space-y-0.5 text-[10px] text-slate-500">
                                        @foreach($q['options'] as $opt)
                                            <div class="@if($opt === $q['correct_option']) text-emerald-600 font-bold @endif">• {{ $opt }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                @if(!empty($q['image_url']))
                                    <div class="mt-2 text-[9px] text-slate-400 font-mono overflow-hidden truncate">URL: {{ $q['image_url'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-400 text-xs italic">
                                Aucune question ajoutée. Utilisez le formulaire à droite pour composer.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right side: Add Question Form -->
                <div class="p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nouvelle Question</h3>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Type de Question</label>
                        <select wire:model.live="question_type" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            <option value="mcq">Choix multiple (Q.C.M.)</option>
                            <option value="text">Texte libre / Question ouverte</option>
                            <option value="drawing">Dessin / Schéma à interpréter</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Intitulé de la Question</label>
                        <textarea wire:model="question_text" rows="2" placeholder="Saisissez la question..." class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                    </div>

                    @if($question_type === 'mcq')
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Options de réponse</label>
                            @foreach($question_options as $optIdx => $optVal)
                                <input type="text" wire:model="question_options.{{ $optIdx }}" placeholder="Option {{ chr(65 + $optIdx) }}" class="block w-full py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                            @endforeach
                            
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mt-2">Bonne Réponse (doit correspondre exactement)</label>
                            <input type="text" wire:model="question_correct" placeholder="Ex: Option A" class="block w-full py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    @endif

                    @if($question_type === 'drawing')
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Lien / URL du Dessin / Schéma</label>
                            <input type="text" wire:model="question_image" placeholder="https://example.com/image.png" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    @endif

                    <button type="button" wire:click="addQuestion" class="w-full py-2 bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:hover:bg-[#222] text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs flex items-center justify-center gap-1">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Ajouter cette question
                    </button>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="saveQuestions" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Enregistrer tout
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: GRADING / CORRECTION -->
    <div x-data="{ open: @entangle('isGradingModalOpen') }"
         class="fixed inset-0 z-50 flex items-center justify-center"
         x-show="open"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm" 
             x-show="open" 
             x-transition.opacity 
             @click="open = false"></div>

        <!-- Dialog Box -->
        <div class="relative w-full max-w-xl bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in"
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
                    <i data-lucide="check-square" class="w-4 h-4 text-indigo-500"></i> Correction de Copie : {{ $grading_student_name }}
                </h2>
                <button @click="open = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Content Form -->
            <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                @php
                    $gradingSub = \App\Models\ClassroomSubmission::with('assessment')->find($selectedSubmissionId);
                    $questionsList = $gradingSub?->assessment?->questions ?: [];
                @endphp
                
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Réponses de l'élève par question</h3>
                <div class="space-y-3">
                    @forelse($questionsList as $idx => $q)
                        <div class="p-3 bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#222] rounded-xl">
                            <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Question {{ $idx + 1 }} • {{ $q['question'] }}</div>
                            @if($q['type'] === 'mcq')
                                <div class="text-xs text-emerald-600 font-semibold mb-1.5">Bonne réponse attendue : {{ $q['correct_option'] }}</div>
                            @endif
                            <label class="block text-[10px] text-slate-500 font-bold mb-1">Réponse de l'élève</label>
                            <input type="text" wire:model="grading_answers.{{ $q['id'] }}" class="block w-full py-1.5 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400 text-xs italic">
                            Aucune question configurée pour ce devoir.
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 dark:border-[#222] pt-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Note Attribuée (max: {{ $gradingSub?->assessment?->max_score ?? 20 }})</label>
                        <input type="number" step="0.5" wire:model="grading_score" placeholder="Ex: 15" class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Commentaires Enseignant</label>
                        <input type="text" wire:model="grading_comments" placeholder="Ex: Très bon devoir." class="block w-full py-2 px-3 text-xs border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0f0f0f] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                <button type="button" @click="open = false" class="flex-1 py-2 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                    Annuler
                </button>
                <button type="button" wire:click="saveGrading" class="flex-1 py-2 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i> Valider la note
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
