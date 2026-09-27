<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ selected: [] }">
    <!-- intl-tel-input dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/intlTelInput.min.js"></script>
    <style>
        .iti { width: 100% !important; }
        .dark .iti__country-list { background-color: #0c0c0c !important; border-color: #333 !important; }
        .dark .iti__country { color: #f8fafc !important; }
        .dark .iti__country:hover { background-color: #1a1a1a !important; }
        .dark .iti__search-input { background-color: #0a0a0a !important; color: #fff !important; border-color: #333 !important; }

        /* Quill JS Dark Mode compatibility overrides */
        .dark .ql-toolbar.ql-snow {
            border-color: #333 !important;
            background-color: #0f0f0f !important;
        }
        .dark .ql-container.ql-snow {
            border-color: #333 !important;
            background-color: #050505 !important;
        }
        .dark .ql-snow .ql-stroke {
            stroke: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-fill {
            fill: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-picker {
            color: #cbd5e1 !important;
        }
        .dark .ql-snow .ql-picker-options {
            background-color: #0f0f0f !important;
            border-color: #333 !important;
        }
        .dark .ql-editor.ql-blank::before {
            color: #475569 !important;
        }
        .dark .ql-editor {
            color: #f8fafc !important;
        }
        .ql-toolbar.ql-snow {
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-color: #e2e8f0;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-color: #e2e8f0;
        }

        /* Print styling for ID Cards */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #id-card-print-area, #id-card-print-area * {
                visibility: visible !important;
            }
            #id-card-print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 20px !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                z-index: 9999 !important;
            }
            @page {
                size: auto;
                margin: 0;
            }
        }
    </style>
    <!-- HEADER & ACTIONS -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white font-space">Annuaire & Recrutement</h1>
            <p class="text-slate-500 text-sm mt-1 font-space">Gérez le personnel enseignant, les dossiers de recrutement et l'annuaire de l'école.</p>
        </div>
        <div class="flex gap-3">
            <button type="button" wire:click="exportPersonnelPdf" class="px-4 py-2 bg-white dark:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-[#333] rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-[#222] transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-indigo-500"></i>
                Exporter PDF
            </button>
            <button type="button" wire:click="createPersonnel" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nouveau Collaborateur
            </button>
        </div>
    </div>

    <!-- TABS Navigation -->
    <div class="flex border-b border-slate-200 dark:border-[#1a1a1a] mb-6">
        <button type="button" wire:click="$set('currentTab', 'annuaire')" class="px-5 py-3 border-b-2 text-sm font-semibold transition-all flex items-center gap-2 {{ $currentTab === 'annuaire' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
            <i data-lucide="users" class="w-4 h-4"></i>
            Annuaire & Recrutement
        </button>
        <button type="button" wire:click="$set('currentTab', 'idcard')" class="px-5 py-3 border-b-2 text-sm font-semibold transition-all flex items-center gap-2 {{ $currentTab === 'idcard' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
            <i data-lucide="contact" class="w-4 h-4"></i>
            Cartes d'identité (ID Cards)
        </button>
    </div>

    @if($currentTab === 'annuaire')
    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[400px]">
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

            <!-- View Mode Toggles -->
            <div class="flex items-center border border-slate-200 dark:border-[#333] rounded-lg p-0.5 bg-slate-50 dark:bg-[#050505]">
                <button type="button" wire:click="$set('viewMode', 'list')" class="p-1 rounded-md transition-colors {{ $viewMode === 'list' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}" title="Mode Liste">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
                <button type="button" wire:click="$set('viewMode', 'grid')" class="p-1 rounded-md transition-colors {{ $viewMode === 'grid' ? 'bg-white dark:bg-[#1a1a1a] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}" title="Mode Grille">
                    <i data-lucide="grid" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        @if($viewMode === 'list')
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
                        @forelse($personnelList as $deptName => $members)
                            <tr class="bg-slate-50/50 dark:bg-[#151515] font-semibold text-xs text-slate-500 uppercase tracking-wider">
                                <td colspan="6" class="px-6 py-2.5 text-indigo-600 dark:text-indigo-400 font-bold font-space">
                                    {{ $deptName }} ({{ count($members) }})
                                </td>
                            </tr>
                            @foreach($members as $item)
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
                                        {{ $item['job'] ?: ($item['specialty'] ?? 'N/A') }}
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
                                            <span class="inline-flex px-2 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-955/30 dark:text-blue-400">En congé</span>
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
                                                <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'terminateContract', 'Êtes-vous sûr de vouloir marquer le départ de ce collaborateur ? son statut passera à Ancien.')" class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 text-rose-700 dark:bg-rose-955/30 dark:text-rose-400 text-xs font-semibold rounded-lg hover:bg-rose-100 transition-colors">
                                                    Départ
                                                </button>
                                            @endif
                                            <button type="button" wire:click="editPersonnel({{ $item['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'deletePersonnel', 'Êtes-vous sûr de vouloir supprimer définitivement ce profil de collaborateur ? Cette action est irréversible.')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucun personnel enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- GRID MODE GROUPED BY DEPARTMENT -->
            <div class="flex-1 overflow-y-auto max-h-[70vh] p-6 space-y-8">
                @forelse($personnelList as $deptName => $members)
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider font-space border-b border-slate-100 dark:border-[#1a1a1a] pb-2 flex items-center justify-between">
                            <span>{{ $deptName }}</span>
                            <span class="text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400 px-2.5 py-0.5 rounded-full">{{ count($members) }} collaborateurs</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach($members as $item)
                                <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-2xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between min-h-[190px]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            @if(!empty($item['avatar']))
                                                <img src="{{ asset('storage/' . $item['avatar']) }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-[#222]">
                                            @else
                                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                                    {{ strtoupper(substr($item['first_name'], 0, 1)) }}{{ strtoupper(substr($item['last_name'], 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <h4 class="font-bold text-slate-900 dark:text-white leading-tight">{{ $item['first_name'] }} {{ $item['last_name'] }}</h4>
                                                <p class="text-xs text-slate-400 mt-1">{{ $item['job'] ?: ($item['specialty'] ?? 'Sans job') }}</p>
                                            </div>
                                        </div>

                                        @if($item['status'] === 'Actif')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">Actif</span>
                                        @elseif($item['status'] === 'Candidat')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400">Candidat</span>
                                        @elseif($item['status'] === 'En congé')
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400">En congé</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">Ancien</span>
                                        @endif
                                    </div>

                                    <div class="space-y-1.5 py-3 my-2 border-y border-slate-100 dark:border-[#1a1a1a] text-xs text-slate-500 dark:text-slate-400">
                                        <div class="flex items-center gap-2 truncate">
                                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span class="truncate" title="{{ $item['email'] }}">{{ $item['email'] ?: 'Non renseigné' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>{{ $item['phone'] ?: 'Non renseigné' }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-1">
                                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $item['salary'] ? number_format($item['salary'], 0, ',', ' ') . ' $' : 'N/A' }}</span>
                                        <div class="flex items-center gap-1">
                                            @if($item['status'] === 'Candidat')
                                                <button type="button" wire:click="signContract({{ $item['id'] }})" class="px-2 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 text-[10px] font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                                                    Signer
                                                </button>
                                            @elseif($item['status'] === 'Actif')
                                                <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'terminateContract', 'Êtes-vous sûr de vouloir marquer le départ de ce collaborateur ? son statut passera à Ancien.')" class="px-2 py-1 bg-rose-50 text-rose-700 dark:bg-rose-955/30 dark:text-rose-400 text-[10px] font-bold rounded-lg hover:bg-rose-100 transition-colors">
                                                    Départ
                                                </button>
                                            @endif
                                            <button type="button" wire:click="editPersonnel({{ $item['id'] }})" class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" wire:click="promptDelete({{ $item['id'] }}, 'deletePersonnel', 'Êtes-vous sûr de vouloir supprimer définitivement ce profil de collaborateur ? Cette action est irréversible.')" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center text-slate-500 py-12">Aucun personnel enregistré.</div>
                @endforelse
            </div>
        @endif
    </div>
    @endif

    @if($currentTab === 'idcard')
        @php
            $themeColors = [
                'tan'     => ['primary' => '#a88e7a', 'dark' => '#8b725e', 'light' => '#d4c4b7'],
                'navy'    => ['primary' => '#1e293b', 'dark' => '#0f172a', 'light' => '#475569'],
                'emerald' => ['primary' => '#065f46', 'dark' => '#064e3b', 'light' => '#10b981'],
                'royal'   => ['primary' => '#3730a3', 'dark' => '#312e81', 'light' => '#818cf8'],
            ];
            $tc = $themeColors[$cardColorTheme] ?? $themeColors['tan'];
            $cardTypeLabels = ['pvc' => 'Carte PVC', 'biometric' => 'Carte Biométrique', 'nfc' => 'Carte NFC'];
            $cardTypeLabel = $cardTypeLabels[$cardType] ?? 'Carte PVC';
        @endphp

        {{-- JsBarcode CDN for real Code128 barcodes --}}
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

            .id-card-front, .id-card-back {
                width: 340px;
                height: 540px;
                border-radius: 18px;
                overflow: hidden;
                position: relative;
                box-shadow:
                    0 30px 60px -15px rgba(0,0,0,0.28),
                    0 0 0 1px rgba(0,0,0,0.04),
                    inset 0 1px 0 rgba(255,255,255,0.1);
                font-family: 'Inter', 'Space Grotesk', sans-serif;
                transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                text-rendering: optimizeLegibility;
            }
            .id-card-front:hover, .id-card-back:hover {
                transform: translateY(-10px) scale(1.015) rotateX(2deg);
            }
            /* Holographic foil effect */
            .holo-foil::before {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(
                    125deg,
                    transparent 35%,
                    rgba(255,255,255,0.06) 42%,
                    rgba(255,255,255,0.12) 48%,
                    rgba(255,255,255,0.06) 54%,
                    transparent 61%
                );
                z-index: 30;
                pointer-events: none;
                animation: holo-sweep 5s ease-in-out infinite;
            }
            @keyframes holo-sweep {
                0%, 100% { transform: translateX(-150%); }
                50% { transform: translateX(150%); }
            }
            .geo-dots {
                background-image:
                    radial-gradient(circle at 20% 30%, rgba(255,255,255,0.07) 1px, transparent 1px),
                    radial-gradient(circle at 80% 70%, rgba(255,255,255,0.05) 1px, transparent 1px);
                background-size: 16px 16px;
            }
            .diagonal-header {
                clip-path: polygon(0 0, 100% 0, 100% 72%, 0 100%);
            }
            /* Typography tokens for print-grade readability */
            .id-label {
                font-family: 'Inter', sans-serif;
                font-size: 7.5px;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: #94a3b8;
                line-height: 1;
            }
            .id-value {
                font-family: 'Inter', sans-serif;
                font-size: 11.5px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.25;
            }
            .id-mono {
                font-family: 'JetBrains Mono', 'Courier New', monospace;
            }
            /* Magnetic stripe */
            .mag-stripe {
                background: repeating-linear-gradient(
                    90deg,
                    #111 0px, #222 2px, #111 4px, #1a1a1a 6px, #333 8px, #111 10px
                );
            }
            /* NFC wave animation */
            @keyframes nfc-pulse {
                0% { transform: scale(0.8); opacity: 0.6; }
                50% { transform: scale(1.2); opacity: 0; }
                100% { transform: scale(0.8); opacity: 0.6; }
            }
            .nfc-wave { animation: nfc-pulse 2s ease-in-out infinite; }
            .nfc-wave-delay { animation: nfc-pulse 2s ease-in-out infinite 0.5s; }

            /* Print optimization */
            @media print {
                .id-card-front, .id-card-back {
                    box-shadow: none !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        </style>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- ═══ LEFT: Controls Panel ═══ -->
            <div class="lg:col-span-1 space-y-5 flex flex-col">
                <div class="p-6 rounded-2xl border border-slate-200 dark:border-[#333] bg-white dark:bg-[#0c0c0c] space-y-5 flex-1 flex flex-col justify-between">
                    <div class="space-y-5">
                        <!-- Header -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                                <i data-lucide="contact" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Générateur ID Cards</h3>
                                <p class="text-[10px] text-slate-400 font-medium">Design professionnel CR-80 • Impression HD</p>
                            </div>
                        </div>

                        <!-- Collaborateur -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Collaborateur</label>
                            <select wire:model.live="selectedPersonnelForCardId" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-xl bg-white dark:bg-[#0a0a0a] dark:text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                                <option value="">— Sélectionner —</option>
                                @foreach($this->teachersList as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }} — {{ $teacher->folder_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type de Carte -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Type de Carte</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach([
                                    'pvc'       => ['icon' => 'credit-card', 'label' => 'PVC Simple'],
                                    'biometric' => ['icon' => 'fingerprint', 'label' => 'Biométrique'],
                                    'nfc'       => ['icon' => 'wifi', 'label' => 'NFC']
                                ] as $typeKey => $typeVal)
                                    <button type="button" wire:click="$set('cardType', '{{ $typeKey }}')"
                                        class="flex flex-col items-center gap-1.5 p-2.5 rounded-xl border text-center transition-all {{ $cardType === $typeKey ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30 ring-1 ring-indigo-400/30' : 'border-slate-200 dark:border-[#333] hover:border-slate-300' }}">
                                        <i data-lucide="{{ $typeVal['icon'] }}" class="w-4 h-4 {{ $cardType === $typeKey ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' }}"></i>
                                        <span class="text-[9px] font-bold {{ $cardType === $typeKey ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $typeVal['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Palette Chromatique -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Palette Chromatique</label>
                            <div class="grid grid-cols-4 gap-2.5">
                                @foreach(['tan' => ['bg' => 'bg-gradient-to-br from-[#c4a882] to-[#8b725e]', 'name' => 'Sahara'], 'navy' => ['bg' => 'bg-gradient-to-br from-[#334155] to-[#0f172a]', 'name' => 'Navy'], 'emerald' => ['bg' => 'bg-gradient-to-br from-[#059669] to-[#064e3b]', 'name' => 'Émeraude'], 'royal' => ['bg' => 'bg-gradient-to-br from-[#6366f1] to-[#312e81]', 'name' => 'Royal']] as $themeKey => $themeVal)
                                    <button type="button" wire:click="$set('cardColorTheme', '{{ $themeKey }}')"
                                        class="group flex flex-col items-center gap-1 p-1.5 rounded-xl border transition-all {{ $cardColorTheme === $themeKey ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 ring-1 ring-indigo-400/30' : 'border-transparent hover:border-slate-200 dark:hover:border-[#333]' }}">
                                        <div class="w-full h-7 rounded-lg {{ $themeVal['bg'] }} shadow-sm relative overflow-hidden">
                                            @if($cardColorTheme === $themeKey)
                                                <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                                                    <i data-lucide="check" class="w-3 h-3 text-white drop-shadow"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-[8px] font-bold {{ $cardColorTheme === $themeKey ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400' }}">{{ $themeVal['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Card Summary -->
                        @php $sel = $this->selectedPersonnel; @endphp
                        @if($sel)
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#111] border border-slate-100 dark:border-[#222]">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-slate-200 dark:bg-[#1a1a1a] flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if($sel->avatar)
                                            <img src="{{ asset('storage/' . $sel->avatar) }}" class="w-full h-full object-cover">
                                        @else
                                            <i data-lucide="user" class="w-5 h-5 text-slate-400"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $sel->last_name }} {{ $sel->first_name }}</p>
                                        <p class="text-[10px] text-slate-500 font-mono">{{ $sel->folder_number }}</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">{{ $cardTypeLabel }} • {{ ucfirst($cardColorTheme) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Print Button -->
                    <div class="pt-4 border-t border-slate-100 dark:border-[#1a1a1a] space-y-2">
                        <button type="button" onclick="window.print()" class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2 transition-all active:scale-[0.98]">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Imprimer la Carte
                        </button>
                        <p class="text-[8px] text-slate-400 dark:text-slate-500 text-center leading-relaxed">
                            Activez <strong>« Graphismes d'arrière-plan »</strong> pour conserver couleurs et dégradés.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ═══ RIGHT: Live Preview ═══ -->
            <div class="lg:col-span-2 flex flex-col items-center justify-center p-8 border border-slate-200 dark:border-[#333] rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/50 dark:from-[#0c0c0c] dark:to-[#111]">
                <div class="flex items-center gap-2 mb-8">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Aperçu temps réel — {{ $cardTypeLabel }}</span>
                </div>

                @if($sel)
                    <div id="id-card-print-area" class="flex flex-col md:flex-row gap-10 items-center justify-center">

                        {{-- ════════════════════════════════════════════
                             CARD FRONT (RECTO)
                        ════════════════════════════════════════════ --}}
                        <div class="id-card-front holo-foil" style="background: #ffffff;">

                            {{-- Diagonal header --}}
                            <div class="h-[180px] relative overflow-hidden diagonal-header" style="background: linear-gradient(135deg, {{ $tc['primary'] }}, {{ $tc['dark'] }});">
                                <div class="absolute inset-0 geo-dots"></div>
                                {{-- Decorative orbs --}}
                                <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full" style="background: {{ $tc['light'] }}; opacity: 0.08;"></div>
                                <div class="absolute -bottom-8 -left-8 w-28 h-28 rounded-full" style="background: {{ $tc['light'] }}; opacity: 0.06;"></div>

                                {{-- Header content --}}
                                <div class="relative z-10 px-6 pt-5 flex justify-between items-start">
                                    {{-- School logo --}}
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 flex items-center justify-center shadow-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        </div>
                                        <div>
                                            <h4 class="text-[11px] font-black text-white uppercase tracking-[0.12em] leading-tight" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">Yeschool</h4>
                                            <p class="text-[8px] text-white/60 font-semibold tracking-wider">Academic Center</p>
                                        </div>
                                    </div>
                                    {{-- Year badge --}}
                                    <div class="px-3 py-1.5 bg-white/12 backdrop-blur-sm rounded-lg border border-white/15 text-right">
                                        <span class="text-[7px] text-white/50 font-bold uppercase tracking-widest block">Année</span>
                                        <span class="text-[12px] text-white font-black" style="font-family:'JetBrains Mono',monospace; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">2026–27</span>
                                    </div>
                                </div>

                                {{-- Card type label at bottom of header --}}
                                <div class="absolute bottom-5 left-6 z-10 flex items-center gap-2">
                                    <span class="text-[8px] font-black text-white/50 uppercase tracking-[0.2em]">{{ $cardTypeLabel }}</span>
                                    @if($cardType === 'nfc')
                                        {{-- NFC icon --}}
                                        <svg class="w-3.5 h-3.5 text-white/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5v14" stroke-linecap="round"/><circle cx="12" cy="12" r="3"/><path d="M6.3 6.3a9 9 0 0112.7 0M4.2 4.2a12 12 0 0115.5 0" stroke-linecap="round"/></svg>
                                    @endif
                                </div>
                            </div>

                            {{-- Photo: overlapping header/body --}}
                            <div class="absolute top-[110px] left-1/2 -translate-x-1/2 z-20">
                                <div class="relative">
                                    <div class="w-[105px] h-[125px] rounded-2xl overflow-hidden border-[4px] border-white" style="box-shadow: 0 16px 40px -8px rgba(0,0,0,0.25);">
                                        @if($sel->avatar)
                                            <img src="{{ asset('storage/' . $sel->avatar) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                                <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                            </div>
                                        @endif
                                        <div class="absolute bottom-0 inset-x-0 h-5 bg-gradient-to-t from-black/25 to-transparent"></div>
                                    </div>
                                    {{-- Biometric chip overlay --}}
                                    @if($cardType === 'biometric')
                                        <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-lg bg-amber-400/90 border-2 border-white shadow-md flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-amber-800" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 6h4v4H6zM14 6h4v4h-4zM6 14h4v4H6zM14 14h4v4h-4z" fill="currentColor" opacity="0.5"/><rect x="10" y="10" width="4" height="4" fill="currentColor"/></svg>
                                        </div>
                                    @endif
                                    {{-- NFC wave icon --}}
                                    @if($cardType === 'nfc')
                                        <div class="absolute -bottom-1 -right-3">
                                            <div class="relative w-8 h-8">
                                                <div class="absolute inset-0 rounded-full border-2 border-indigo-400/40 nfc-wave"></div>
                                                <div class="absolute inset-1 rounded-full border-2 border-indigo-400/60 nfc-wave-delay"></div>
                                                <div class="absolute inset-2.5 rounded-full bg-indigo-500 flex items-center justify-center">
                                                    <svg class="w-2 h-2 text-white" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="4"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Card body --}}
                            <div class="px-7 pt-[80px] pb-4 flex-1 flex flex-col justify-between relative">
                                {{-- Name & Title --}}
                                <div class="text-center mb-3">
                                    <h2 class="text-[16px] font-black text-slate-900 uppercase tracking-wide leading-tight" style="font-family:'Inter',sans-serif; text-shadow: 0 0.5px 0 rgba(0,0,0,0.05);">{{ $sel->last_name }}</h2>
                                    <h3 class="text-[14px] font-semibold text-slate-500 mt-0.5" style="font-family:'Inter',sans-serif;">{{ $sel->first_name }}</h3>
                                    <div class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider" style="background: {{ $tc['primary'] }}12; color: {{ $tc['primary'] }};">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $tc['primary'] }};"></span>
                                        {{ $sel->job ?: $sel->specialty ?: 'Personnel' }}
                                    </div>
                                </div>

                                {{-- Details --}}
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-px bg-slate-100"></div>
                                        <span class="text-[7px] font-extrabold text-slate-300 uppercase tracking-[0.2em]">Détails</span>
                                        <div class="flex-1 h-px bg-slate-100"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                        <div>
                                            <span class="id-label block">Département</span>
                                            <p class="id-value">{{ $sel->department ? $sel->department->name : '—' }}</p>
                                        </div>
                                        <div>
                                            <span class="id-label block">Qualité</span>
                                            <p class="id-value">{{ $sel->acting_as ?: '—' }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Matricule badge --}}
                                <div class="mt-3 flex items-center justify-between p-2.5 rounded-xl border border-slate-100" style="background: linear-gradient(135deg, {{ $tc['primary'] }}0a, {{ $tc['primary'] }}04);">
                                    <div>
                                        <span class="id-label block mb-0.5">Matricule</span>
                                        <span class="text-[14px] font-black id-mono tracking-wider" style="color: {{ $tc['primary'] }};">{{ $sel->folder_number }}</span>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: {{ $tc['primary'] }}10;">
                                        <i data-lucide="fingerprint" class="w-4 h-4" style="color: {{ $tc['primary'] }};"></i>
                                    </div>
                                </div>
                            </div>

                            {{-- Bottom gradient strip --}}
                            <div class="h-2 w-full" style="background: linear-gradient(90deg, {{ $tc['primary'] }}, {{ $tc['dark'] }});"></div>
                        </div>

                        {{-- ════════════════════════════════════════════
                             CARD BACK (VERSO)
                        ════════════════════════════════════════════ --}}
                        <div class="id-card-back" style="background: #fafbfc;">
                            {{-- Top gradient --}}
                            <div class="h-2 w-full" style="background: linear-gradient(90deg, {{ $tc['dark'] }}, {{ $tc['primary'] }});"></div>

                            {{-- Magnetic stripe --}}
                            <div class="mag-stripe h-10 mt-3 w-full"></div>

                            {{-- SVG Watermark at 60% opacity --}}
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0" style="opacity: 0.06;">
                                <svg class="w-[260px] h-[260px]" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    {{-- Graduation cap / academic SVG watermark --}}
                                    <g fill="{{ $tc['primary'] }}">
                                        <path d="M100 30L20 70l80 40 80-40-80-40z"/>
                                        <path d="M40 80v40c0 20 27 36 60 36s60-16 60-36V80L100 120 40 80z"/>
                                        <rect x="155" y="68" width="6" height="60" rx="3"/>
                                        <circle cx="158" cy="135" r="8"/>
                                    </g>
                                </svg>
                            </div>

                            <div class="flex-1 px-7 py-4 flex flex-col justify-between relative z-10">
                                {{-- School header --}}
                                <div class="text-center space-y-1.5">
                                    <div class="w-10 h-10 mx-auto rounded-2xl flex items-center justify-center shadow-md" style="background: linear-gradient(135deg, {{ $tc['primary'] }}, {{ $tc['dark'] }});">
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-[0.12em]" style="font-family:'Inter',sans-serif;">Yeschool Academic Center</h4>
                                        <p class="text-[8px] text-slate-400 font-medium">Plateforme de gestion scolaire moderne</p>
                                    </div>
                                </div>

                                {{-- Legal notice --}}
                                <div class="mt-3 p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                                    <p class="text-[7.5px] font-black text-slate-600 uppercase tracking-[0.12em] text-center mb-1.5" style="font-family:'Inter',sans-serif;">Conditions d'utilisation</p>
                                    <p class="text-[8.5px] text-slate-500 leading-[1.55] text-justify" style="font-family:'Inter',sans-serif;">
                                        Cette carte professionnelle est strictement personnelle et incessible. Elle demeure la propriété exclusive de l'établissement et doit être portée visiblement durant la présence sur le campus.
                                    </p>
                                    <p class="text-[8.5px] text-slate-500 leading-[1.55] text-justify mt-1" style="font-family:'Inter',sans-serif;">
                                        En cas de perte, contacter l'administration au <strong class="text-slate-700">+243 000 000 000</strong> ou <strong class="text-slate-700">admin@yeschool.cd</strong>.
                                    </p>
                                </div>

                                {{-- Real Barcode (JsBarcode) --}}
                                <div class="mt-3 flex flex-col items-center">
                                    <div class="bg-white px-3 py-2 rounded-lg border border-slate-100 shadow-sm">
                                        <svg id="barcode-{{ $sel->id }}" class="barcode-svg"></svg>
                                    </div>
                                    <span class="mt-1 text-[9px] font-bold id-mono text-slate-500 tracking-[0.25em]">{{ $sel->folder_number }}</span>
                                </div>

                                {{-- Card type indicator --}}
                                <div class="mt-2 flex items-center justify-center gap-2">
                                    @if($cardType === 'biometric')
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><rect x="10" y="10" width="4" height="4" fill="currentColor"/></svg>
                                            <span class="text-[8px] font-bold text-amber-700 uppercase tracking-wider">Puce Biométrique</span>
                                        </div>
                                    @elseif($cardType === 'nfc')
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200">
                                            <svg class="w-3 h-3 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M6.3 6.3a9 9 0 0112.7 0M4.2 4.2a12 12 0 0115.5 0" stroke-linecap="round"/></svg>
                                            <span class="text-[8px] font-bold text-indigo-700 uppercase tracking-wider">Technologie NFC</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200">
                                            <i data-lucide="credit-card" class="w-3 h-3 text-slate-500"></i>
                                            <span class="text-[8px] font-bold text-slate-600 uppercase tracking-wider">PVC Standard</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Footer: Signature + Date --}}
                                <div class="mt-2 pt-2.5 border-t border-slate-100 flex justify-between items-end">
                                    <div class="space-y-1">
                                        <div class="w-20 border-b border-slate-300 border-dashed"></div>
                                        <span class="text-[7px] font-bold text-slate-400 uppercase tracking-wider block" style="font-family:'Inter',sans-serif;">Signature du Titulaire</span>
                                    </div>
                                    <div class="text-right space-y-0.5">
                                        <span class="text-[7px] font-bold text-slate-400 uppercase tracking-wider block" style="font-family:'Inter',sans-serif;">Délivré le</span>
                                        <span class="text-[10px] font-bold text-slate-700 id-mono">{{ now()->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Bottom strip --}}
                            <div class="h-2 w-full" style="background: linear-gradient(90deg, {{ $tc['primary'] }}, {{ $tc['dark'] }});"></div>
                        </div>
                    </div>

                    {{-- Recto / Verso labels --}}
                    <div class="flex gap-10 mt-6 justify-center">
                        <span class="text-[9px] font-bold text-slate-300 uppercase tracking-[0.25em]">← Recto</span>
                        <span class="text-[9px] font-bold text-slate-300 uppercase tracking-[0.25em]">Verso →</span>
                    </div>

                    {{-- Initialize JsBarcode --}}
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            initBarcode();
                        });
                        document.addEventListener('livewire:navigated', function() {
                            initBarcode();
                        });
                        if (typeof Livewire !== 'undefined') {
                            Livewire.hook('morph.updated', () => { setTimeout(initBarcode, 100); });
                        }
                        function initBarcode() {
                            const el = document.querySelector('.barcode-svg');
                            if (el && typeof JsBarcode !== 'undefined') {
                                try {
                                    JsBarcode(el, "{{ $sel->folder_number }}", {
                                        format: "CODE128",
                                        width: 1.5,
                                        height: 40,
                                        displayValue: false,
                                        margin: 0,
                                        background: "transparent",
                                        lineColor: "#1e293b"
                                    });
                                } catch(e) { console.warn('Barcode error:', e); }
                            }
                        }
                        setTimeout(initBarcode, 300);
                    </script>
                @else
                    <div class="flex flex-col items-center text-center py-16 space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-[#1a1a1a] flex items-center justify-center">
                            <i data-lucide="contact" class="w-7 h-7 text-slate-300 dark:text-slate-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Aucun collaborateur sélectionné</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Sélectionnez un membre du personnel pour générer sa carte ID.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- SIDE-OVER: ADD / EDIT PERSONNEL -->
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
         class="fixed inset-y-0 right-0 z-[999] max-w-5xl w-full bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a] flex flex-col h-full overflow-hidden" 
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
            
            <!-- Stepper Header -->
            <div class="px-6 py-4 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#0c0c0c]/10">
                <div class="flex items-center w-full max-w-2xl mx-auto justify-between">
                    <!-- Step 1 Indicator -->
                    <button type="button" wire:click="goToStep(1)" class="flex items-center gap-2 focus:outline-none">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $form_step === 1 ? 'bg-indigo-600 text-white ring-4 ring-indigo-500/20' : ($form_step > 1 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400' : 'bg-slate-100 text-slate-500 dark:bg-[#222]') }}">1</span>
                        <span class="text-xs font-semibold {{ $form_step === 1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }} hidden sm:inline">Perso</span>
                    </button>
                    <div class="flex-1 h-0.5 mx-2 bg-slate-200 dark:bg-slate-800"></div>
                    
                    <!-- Step 2 Indicator -->
                    <button type="button" wire:click="goToStep(2)" class="flex items-center gap-2 focus:outline-none">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $form_step === 2 ? 'bg-indigo-600 text-white ring-4 ring-indigo-500/20' : ($form_step > 2 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400' : 'bg-slate-100 text-slate-500 dark:bg-[#222]') }}">2</span>
                        <span class="text-xs font-semibold {{ $form_step === 2 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }} hidden sm:inline">Famille</span>
                    </button>
                    <div class="flex-1 h-0.5 mx-2 bg-slate-200 dark:bg-slate-800"></div>
                    
                    <!-- Step 3 Indicator -->
                    <button type="button" wire:click="goToStep(3)" class="flex items-center gap-2 focus:outline-none">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $form_step === 3 ? 'bg-indigo-600 text-white ring-4 ring-indigo-500/20' : ($form_step > 3 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400' : 'bg-slate-100 text-slate-500 dark:bg-[#222]') }}">3</span>
                        <span class="text-xs font-semibold {{ $form_step === 3 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }} hidden sm:inline">Poste</span>
                    </button>
                    <div class="flex-1 h-0.5 mx-2 bg-slate-200 dark:bg-slate-800"></div>
                    
                    <!-- Step 4 Indicator -->
                    <button type="button" wire:click="goToStep(4)" class="flex items-center gap-2 focus:outline-none">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $form_step === 4 ? 'bg-indigo-600 text-white ring-4 ring-indigo-500/20' : 'bg-slate-100 text-slate-500 dark:bg-[#222]' }}">4</span>
                        <span class="text-xs font-semibold {{ $form_step === 4 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500' }} hidden sm:inline">Contrat & Docs</span>
                    </button>
                </div>
            </div>

            <!-- Scrollable Body -->
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-8 bg-white dark:bg-[#0a0a0a]">
                
                @if($form_step === 1)
                    <!-- STEP 1: INFORMATIONS PERSONNELLES -->
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
                                    <input type="text" wire:model="form_first_name" class="block w-full py-2 px-3 text-sm border @error('form_first_name') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Jean" required>
                                    @error('form_first_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom</label>
                                    <input type="text" wire:model="form_last_name" class="block w-full py-2 px-3 text-sm border @error('form_last_name') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Dupont" required>
                                    @error('form_last_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Adresse Email</label>
                                <input type="email" wire:model="form_email" class="block w-full py-2 px-3 text-sm border @error('form_email') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: jean.dupont@example.com">
                                @error('form_email') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone field with Flags (intl-tel-input) -->
                            <div>
                                <div x-data="{
                                    iti: null,
                                    phoneValue: @entangle('form_phone')
                                }" x-init="
                                    $nextTick(() => {
                                        iti = window.intlTelInput($refs.phoneInput, {
                                            initialCountry: 'cd',
                                            preferredCountries: ['cd', 'be', 'fr', 'us'],
                                            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.10/build/js/utils.js'
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
                                        $refs.phoneInput.addEventListener('input', () => {
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
                                    <input x-ref="phoneInput" type="tel" class="block w-full py-2 px-3 text-sm border @error('form_phone') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: +243812345678">
                                </div>
                                @error('form_phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Date de naissance</label>
                                    <input type="date" wire:model="form_date_of_birth" class="block w-full py-2 px-3 text-sm border @error('form_date_of_birth') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    @error('form_date_of_birth') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Lieu de naissance</label>
                                    <input type="text" wire:model="form_place_of_birth" class="block w-full py-2 px-3 text-sm border @error('form_place_of_birth') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Kinshasa, Paris">
                                    @error('form_place_of_birth') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($form_step === 2)
                    <!-- STEP 2: ÉTAT CIVIL & FAMILLE -->
                    <div class="max-w-2xl mx-auto p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 space-y-5">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Situation Familiale & État Civil</span>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">État Civil</label>
                                <select wire:model.live="form_civil_status" class="block w-full py-2 px-3 text-sm border @error('form_civil_status') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" required>
                                    <option value="Célibataire">Célibataire</option>
                                    <option value="Marié">Marié(e)</option>
                                    <option value="Divorcé">Divorcé(e)</option>
                                    <option value="Veuf">Veuf/Veuve</option>
                                </select>
                                @error('form_civil_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nombre d'enfants</label>
                                <input type="number" min="0" wire:model.live="form_children_count" class="block w-full py-2 px-3 text-sm border @error('form_children_count') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                @error('form_children_count') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Conditional: Spouse Name if Marié -->
                        @if($form_civil_status === 'Marié')
                            <div class="animate-fade-in">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom du Conjoint (Épouse / Conjoint)</label>
                                <input type="text" wire:model="form_spouse_name" class="block w-full py-2 px-3 text-sm border @error('form_spouse_name') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Saisir le nom complet">
                                @error('form_spouse_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Conditional: Children Names if children_count > 0 -->
                        @if(intval($form_children_count) > 0)
                            <div class="animate-fade-in">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Noms des enfants</label>
                                <textarea wire:model="form_children_names" rows="3" class="block w-full py-2 px-3 text-sm border @error('form_children_names') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Saisir les noms des enfants"></textarea>
                                @error('form_children_names') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>
                @endif

                @if($form_step === 3)
                    <!-- STEP 3: INFORMATIONS PROFESSIONNELLES & QUALIFICATION -->
                    <div class="max-w-2xl mx-auto p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 space-y-5">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Poste & Qualifications</span>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Type de poste</label>
                                <select wire:model="form_job_type" class="block w-full py-2 px-3 text-sm border @error('form_job_type') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    <option value="">Sélectionner</option>
                                    <option value="Temps plein">Temps plein</option>
                                    <option value="Temps partiel">Temps partiel</option>
                                    <option value="Prestataire externe">Prestataire externe</option>
                                    <option value="Vacataire">Vacataire</option>
                                </select>
                                @error('form_job_type') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Numéro dossier</label>
                                <input type="text" wire:model="form_folder_number" class="block w-full py-2 px-3 text-sm border @error('form_folder_number') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-slate-50 dark:bg-[#151515] dark:text-slate-300" readonly placeholder="Généré automatiquement">
                                @error('form_folder_number') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Job / Titre d'emploi</label>
                                <select wire:model="form_job" class="block w-full py-2 px-3 text-sm border @error('form_job') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    <option value="">Sélectionner</option>
                                    <option value="Professeur">Professeur</option>
                                    <option value="Secrétaire">Secrétaire</option>
                                    <option value="Comptable">Comptable</option>
                                    <option value="Assistant RH">Assistant RH</option>
                                    <option value="Directeur">Directeur</option>
                                    <option value="Surveillant">Surveillant</option>
                                    <option value="Agent d'entretien">Agent d'entretien</option>
                                    <option value="Agent de sécurité">Agent de sécurité</option>
                                    <option value="Chauffeur">Chauffeur</option>
                                </select>
                                @error('form_job') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Agissant en qualité de</label>
                                <select wire:model="form_acting_as" class="block w-full py-2 px-3 text-sm border @error('form_acting_as') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    <option value="">Sélectionner</option>
                                    <option value="Titulaire">Titulaire</option>
                                    <option value="Contractuel">Contractuel</option>
                                    <option value="Vacataire">Vacataire</option>
                                    <option value="Stagiaire">Stagiaire</option>
                                    <option value="Prestataire externe">Prestataire externe</option>
                                </select>
                                @error('form_acting_as') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 font-space">Qualification académique</label>
                            <select wire:model="form_academic_qualification" class="block w-full py-2 px-3 text-sm border @error('form_academic_qualification') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                <option value="">Sélectionner</option>
                                <option value="Doctorat (Ph.D.)">Doctorat (Ph.D.)</option>
                                <option value="Master">Master</option>
                                <option value="Licence (Bachelor)">Licence (Bachelor)</option>
                                <option value="Graduat / BTS">Graduat / BTS</option>
                                <option value="Diplôme d'État / Baccalauréat">Diplôme d'État / Baccalauréat</option>
                                <option value="Autre">Autre</option>
                            </select>
                            @error('form_academic_qualification') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 font-space">Nom du Responsable</label>
                                <input type="text" wire:model="form_manager_name" class="block w-full py-2 px-3 text-sm border @error('form_manager_name') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: Jean Mukendi">
                                @error('form_manager_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 font-space">Rattachement Département</label>
                                <select wire:model="form_department_id" class="block w-full py-2 px-3 text-sm border @error('form_department_id') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    <option value="">-- Sans département --</option>
                                    @foreach($departmentsList as $d)
                                        <option value="{{ $d['id'] }}">{{ $d['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('form_department_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Fonction / Rôle</label>
                                <select wire:model="form_specialty" class="block w-full py-2 px-3 text-sm border @error('form_specialty') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" required>
                                    <option value="">Sélectionner</option>
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
                                @error('form_specialty') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Salaire base ($)</label>
                                <input type="number" step="0.01" wire:model="form_salary" class="block w-full py-2 px-3 text-sm border @error('form_salary') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white" placeholder="Ex: 2500">
                                @error('form_salary') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Statut RH</label>
                                <select wire:model="form_status" class="block w-full py-2 px-3 text-sm border @error('form_status') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                    <option value="Candidat">Candidat</option>
                                    <option value="Actif">Actif / Collaborateur</option>
                                    <option value="En congé">En congé / Absent</option>
                                    <option value="Ancien">Ancien / Départ</option>
                                </select>
                                @error('form_status') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                @if($form_step === 4)
                    <!-- STEP 4: CONTRAT & DOCUMENTS -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left: Dates and Files -->
                        <div class="lg:col-span-1 p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 space-y-4">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Contrat & Pièces Jointes</span>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Signature Contrat</label>
                                <input type="date" wire:model="form_contract_signed_at" class="block w-full py-2 px-3 text-sm border @error('form_contract_signed_at') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                @error('form_contract_signed_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Fin Contrat</label>
                                <input type="date" wire:model="form_contract_ended_at" class="block w-full py-2 px-3 text-sm border @error('form_contract_ended_at') border-rose-500 focus:ring-rose-500 @else border-slate-200 dark:border-[#333] focus:ring-indigo-500 @enderror rounded-lg bg-white dark:bg-[#0a0a0a] dark:text-white">
                                @error('form_contract_ended_at') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

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
                                    @error('cv_upload') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
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
                                    @error('cover_letter_upload') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Right: Quill text editor notes -->
                        <div class="lg:col-span-2 p-6 rounded-2xl border border-slate-200 dark:border-[#1a1a1a] bg-white dark:bg-[#0c0c0c]/10 flex flex-col justify-between space-y-4">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2 font-space">Notes de Recrutement & Dossier</span>

                            <div class="flex-1" x-data="{
                                quill: null,
                                content: @entangle('form_recruitment_notes')
                            }" x-init="
                                $nextTick(() => {
                                    quill = new Quill($refs.editor, {
                                        theme: 'snow',
                                        placeholder: 'Saisissez les notes de recrutement, parcours, appréciations...',
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
                                });
                            }" wire:ignore>
                                <div class="bg-white dark:bg-[#050505] rounded-xl overflow-hidden border border-slate-200 dark:border-[#333] shadow-sm">
                                    <div x-ref="editor" class="min-h-[160px] text-sm text-slate-900 dark:text-white"></div>
                                </div>
                            </div>
                            @error('form_recruitment_notes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

            </div>

            <!-- Sticky Action Footer -->
            <div class="border-t border-slate-100 dark:border-[#1a1a1a] px-6 py-4 bg-slate-50/50 dark:bg-[#0a0a0a]/90 flex justify-between gap-3 sticky bottom-0 z-10 backdrop-blur-sm">
                <div>
                    @if($form_step > 1)
                        <button type="button" wire:click="goToStep({{ $form_step - 1 }})" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                            Précédent
                        </button>
                    @endif
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="$wire.isPersonnelOffcanvasOpen = false" class="px-5 py-2 text-sm bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                        Annuler
                    </button>
                    @if($form_step < 4)
                        <button type="button" wire:click="goToStep({{ $form_step + 1 }})" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                            Suivant
                        </button>
                    @endif
                    @if($form_step === 4)
                        <button type="submit" class="px-6 py-2 text-sm bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20">
                            Enregistrer
                        </button>
                    @endif
                </div>
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
