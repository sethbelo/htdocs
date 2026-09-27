<div class="h-full flex flex-col p-6 max-w-7xl mx-auto" x-data="{ activeTab: @entangle('activeTab') }">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8 no-print">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Inscriptions & Réinscriptions</h1>
            <p class="text-slate-500 text-sm mt-1">Gérez l'arrivée des nouveaux élèves admis ou confirmez le renouvellement d'une année pour les élèves existants.</p>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-lg text-sm flex items-center gap-3 no-print">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- TABS NAVIGATION -->
    <div class="flex border-b border-slate-100 dark:border-[#1a1a1a] gap-6 no-print">
        <button @click="$wire.switchTab('inscription')" :class="{ 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400': activeTab === 'inscription', 'border-transparent text-slate-400 hover:text-slate-900 dark:hover:text-white': activeTab !== 'inscription' }" class="pb-3 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Nouvelle Inscription
        </button>
        <button @click="$wire.switchTab('reinscription')" :class="{ 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400': activeTab === 'reinscription', 'border-transparent text-slate-400 hover:text-slate-900 dark:hover:text-white': activeTab !== 'reinscription' }" class="pb-3 border-b-2 font-semibold text-sm transition-all flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            Réinscription (Ancien Élève)
        </button>
    </div>

    <!-- TABS CONTENT -->
    <div class="no-print">
        
        <!-- 1. TAB: INSCRIPTION -->
        <div x-show="activeTab === 'inscription'" class="space-y-6">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm p-6 max-w-2xl">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-6">Confirmer l'Admission d'un Candidat</h3>
                
                <form wire:submit.prevent="submitInscription" class="space-y-6">
                    <!-- Sélection de l'admis validé -->
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        selectedId: @entangle('admission_id'),
                        options: @js($acceptedAdmissions),
                        get selectedLabel() {
                            const option = this.options.find(opt => opt.id == this.selectedId);
                            return option ? `${option.first_name} ${option.last_name} (Classe demandée: ${option.requested_class})` : 'Sélectionnez un candidat admis';
                        },
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            return this.options.filter(opt => {
                                const fullName = `${opt.first_name} ${opt.last_name}`.toLowerCase();
                                const reqClass = (opt.requested_class || '').toLowerCase();
                                return fullName.includes(this.search.toLowerCase()) || reqClass.includes(this.search.toLowerCase());
                            });
                        },
                        selectOption(id) {
                            this.selectedId = id;
                            this.open = false;
                            this.search = '';
                        }
                    }" x-init="$watch('open', val => { if(val) { $nextTick(() => { $refs.searchInput.focus(); }); } })" @click.outside="open = false">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Nom du candidat admis <span class="text-rose-500">*</span></label>
                        
                        <!-- Trigger Button -->
                        <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-left transition-all">
                            <span x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <!-- Dropdown Content -->
                        <div x-show="open" x-cloak class="absolute z-40 w-full mt-1 bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-lg shadow-xl overflow-hidden animate-fade-in">
                            <!-- Search Input -->
                            <div class="p-2 border-b border-slate-100 dark:border-[#1a1a1a] flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" x-ref="searchInput" x-model="search" placeholder="Rechercher un candidat..." class="w-full py-1 text-xs bg-transparent border-none text-slate-900 dark:text-white focus:ring-0 placeholder-slate-400 focus:outline-none">
                            </div>
                            
                            <!-- Options List -->
                            <ul class="max-h-60 overflow-y-auto p-1 space-y-0.5">
                                <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-xs text-slate-400 text-center">
                                    Aucun candidat trouvé
                                </li>
                                <template x-for="adm in filteredOptions" :key="adm.id">
                                    <li>
                                        <button type="button" @click="selectOption(adm.id)" :class="selectedId == adm.id ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#1a1a1a]'" class="w-full text-left px-3 py-2 text-xs rounded-md transition-colors flex items-center justify-between">
                                            <span x-text="`${adm.first_name} ${adm.last_name} (Classe: ${adm.requested_class})`"></span>
                                            <svg x-show="selectedId == adm.id" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        @error('admission_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Matricule unique généré -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Matricule (Généré automatiquement)</label>
                        <input type="text" wire:model="enroll_matricule" readonly class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-slate-100 dark:bg-[#111] text-slate-500 dark:text-slate-400 focus:outline-none cursor-not-allowed">
                    </div>

                    <!-- Année Scolaire & Classe -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Année Scolaire</label>
                            <select wire:model.live="enroll_academic_year_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
                                @endforeach
                            </select>
                            @error('enroll_academic_year_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Classe d'Affectation</label>
                            <select wire:model="enroll_class_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                @foreach($schoolClasses as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                                @endforeach
                            </select>
                            @error('enroll_class_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Avatar / Photo élève -->
                    <div class="space-y-3" x-data="{
                         stream: null,
                         cameraActive: false,
                         capturedImage: null,
                         initWebcam() {
                             navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
                             .then(str => {
                                 this.stream = str;
                                 this.$refs.video.srcObject = str;
                                 this.$refs.video.play();
                                 this.cameraActive = true;
                             })
                             .catch(err => {
                                 alert('Webcam inaccessible : ' + err.message);
                             });
                         },
                         capturePhoto() {
                             const canvas = this.$refs.canvas;
                             const context = canvas.getContext('2d');
                             canvas.width = this.$refs.video.videoWidth || 320;
                             canvas.height = this.$refs.video.videoHeight || 240;
                             context.drawImage(this.$refs.video, 0, 0, canvas.width, canvas.height);
                             this.capturedImage = canvas.toDataURL('image/jpeg');
                             @this.set('enroll_avatar_webcam', this.capturedImage);
                             this.stopWebcam();
                         },
                         stopWebcam() {
                             if (this.stream) {
                                 this.stream.getTracks().forEach(track => track.stop());
                                 this.stream = null;
                             }
                             this.cameraActive = false;
                         }
                    }" x-init="$watch('activeTab', value => { stopWebcam(); })">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Photo de l'élève (Upload ou Caméra)</label>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Fichier -->
                            <div class="p-4 border border-dashed border-slate-200 dark:border-[#333] rounded-xl text-center flex flex-col items-center justify-center bg-slate-50/50 dark:bg-[#111]/30">
                                <i data-lucide="upload" class="w-6 h-6 text-slate-400 mb-2"></i>
                                <label class="cursor-pointer text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Charger un fichier
                                    <input type="file" wire:model="enroll_avatar_upload" class="hidden" accept="image/*">
                                </label>
                                @if ($enroll_avatar_upload)
                                    <span class="text-[10px] text-emerald-500 mt-1">Image chargée</span>
                                @endif
                            </div>

                            <!-- Caméra -->
                            <div class="p-4 border border-dashed border-slate-200 dark:border-[#333] rounded-xl text-center flex flex-col items-center justify-center bg-slate-50/50 dark:bg-[#111]/30">
                                <i data-lucide="camera" class="w-6 h-6 text-slate-400 mb-2"></i>
                                <button type="button" @click="initWebcam()" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Prendre via Webcam
                                </button>
                                <template x-if="capturedImage">
                                    <span class="text-[10px] text-emerald-500 mt-1">Photo capturée</span>
                                </template>
                            </div>
                        </div>

                        <!-- Webcam live video container -->
                        <div x-show="cameraActive" class="p-3 bg-slate-900 rounded-xl space-y-3">
                            <video x-ref="video" class="w-full rounded-lg bg-black h-40 object-cover" autoplay playsinline></video>
                            <canvas x-ref="canvas" class="hidden"></canvas>
                            <div class="flex gap-2">
                                <button type="button" @click="capturePhoto()" class="flex-1 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700">Capturer la photo</button>
                                <button type="button" @click="stopWebcam()" class="py-1.5 bg-slate-700 text-white rounded-lg text-xs font-semibold hover:bg-slate-600">Annuler</button>
                            </div>
                        </div>

                        <!-- Preview elements -->
                        @if($enroll_avatar_upload)
                            <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-[#111] rounded-lg">
                                <img src="{{ $enroll_avatar_upload->temporaryUrl() }}" class="w-12 h-12 rounded-lg object-cover">
                                <span class="text-xs text-slate-500">Aperçu de la photo chargée</span>
                            </div>
                        @endif
                        <template x-if="capturedImage">
                            <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-[#111] rounded-lg">
                                <img :src="capturedImage" class="w-12 h-12 rounded-lg object-cover">
                                <span class="text-xs text-slate-500">Aperçu capture Webcam</span>
                            </div>
                        </template>
                    </div>

                    <!-- Comptabilité validation info -->
                    <div class="p-4 bg-indigo-500/10 border border-indigo-500/20 rounded-xl flex gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-indigo-500 shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-indigo-800 dark:text-indigo-400">Paiement Comptable d'Inscription</p>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">La validation générera automatiquement une transaction comptable de **150 USD** pour les Frais d'Inscription.</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="submitInscription(false)" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-[#111] dark:hover:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all flex items-center justify-center gap-2">
                            <i data-lucide="save" class="w-5 h-5"></i> Valider uniquement
                        </button>
                        <button type="button" wire:click="submitInscription(true)" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i> Valider & Imprimer le reçu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. TAB: REINSCRIPTION -->
        <div x-show="activeTab === 'reinscription'" class="space-y-6">
            <div class="bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#333] rounded-2xl shadow-sm p-6 max-w-2xl">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-6">Réinscrire un Élève Existant</h3>

                <form wire:submit.prevent="submitReinscription" class="space-y-6">
                    <!-- Recherche Matricule -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Matricule de l'élève <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="text" wire:model.live="re_matricule" placeholder="Ex: MAT-2025-2026-0001" class="block w-full py-2.5 pl-10 pr-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3.5"></i>
                        </div>
                        @error('re_matricule') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Retour d'élève trouvé -->
                    @if($re_student_found)
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl space-y-2">
                            <div class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-400">
                                <i data-lucide="user-check" class="w-5 h-5"></i> Élève trouvé !
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300">Nom : <strong>{{ $re_student_name }}</strong></p>
                            <p class="text-xs text-slate-600 dark:text-slate-300">Classe actuelle : <strong>{{ $re_student_current_class }}</strong></p>
                        </div>
                    @elseif($re_matricule)
                        <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 rounded-xl flex items-center gap-2 text-xs text-rose-700 dark:text-rose-400">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i> Aucun élève correspondant à ce matricule.
                        </div>
                    @endif

                    <!-- Nouvelle Classe & Année Scolaire -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Nouvelle Année Scolaire</label>
                            <select wire:model.live="re_academic_year_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
                                @endforeach
                            </select>
                            @error('re_academic_year_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Classe d'Affectation</label>
                            <select wire:model="re_class_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                @foreach($schoolClasses as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                                @endforeach
                            </select>
                            @error('re_class_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Comptabilité validation info -->
                    <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-amber-500 shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-400">Paiement Comptable de Réinscription</p>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">La validation créera automatiquement une entrée comptable de **80 USD** pour les Frais de Réinscription.</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" wire:click="submitReinscription(false)" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-[#111] dark:hover:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-all flex items-center justify-center gap-2" {{ !$re_student_found ? 'disabled' : '' }}>
                            <i data-lucide="save" class="w-5 h-5"></i> Valider uniquement
                        </button>
                        <button type="button" wire:click="submitReinscription(true)" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2" {{ !$re_student_found ? 'disabled' : '' }}>
                            <i data-lucide="printer" class="w-5 h-5"></i> Valider & Imprimer le reçu
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- 3. STYLE IMPRESSION & OVERLAY POUR LES RECUS -->
    <style>
        @media print {
            /* Hide layout elements completely to avoid offset and page truncation */
            aside, header, nav, .no-print, .no-print * {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            html, body, main, div, section {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                box-shadow: none !important;
                border: none !important;
                width: auto !important;
                height: auto !important;
                overflow: visible !important;
                display: block !important;
                position: static !important;
            }
            
            body * {
                visibility: hidden !important;
            }
            .print-modal-container, .print-modal-container * {
                visibility: visible !important;
            }
            #print-receipt-section, #print-receipt-section * {
                visibility: visible !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            .print-modal-container {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: white !important;
                backdrop-filter: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                display: block !important;
            }
            .print-modal-container > div {
                box-shadow: none !important;
                border: none !important;
                background: white !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            /* A4 print layouts */
            .print-format-a4 {
                width: 190mm !important; /* safe width inside printable A4 area */
                margin: 0 auto !important;
                padding: 10mm !important;
                box-sizing: border-box !important;
                border: none !important;
            }
            
            /* A5 print layouts */
            .print-format-a5 {
                width: 136mm !important; /* safe width inside printable A5 area */
                margin: 0 auto !important;
                padding: 8mm !important;
                box-sizing: border-box !important;
                border: none !important;
            }
            
            /* Thermal Ticket format (80x80) */
            .print-format-ticket {
                width: 74mm !important; /* safe width inside 80mm roll */
                margin: 0 auto !important;
                padding: 4mm 2mm !important;
                box-sizing: border-box !important;
                border: none !important;
                font-size: 8.5px !important;
                line-height: 1.25 !important;
            }
            
            .print-format-ticket h2 {
                font-size: 11px !important;
                text-align: center !important;
            }
            .print-format-ticket p, 
            .print-format-ticket span, 
            .print-format-ticket td, 
            .print-format-ticket th {
                font-size: 8px !important;
            }
            
            .print-format-ticket .print-grid {
                display: block !important;
                text-align: left !important;
            }
            .print-format-ticket .print-grid > div {
                margin-bottom: 6px !important;
                text-align: left !important;
            }
            
            .print-format-ticket .print-flex {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 4px !important;
            }
            
            .print-format-ticket .print-logo-container {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 6px !important;
            }
            .print-format-ticket .print-logo-container > div {
                text-align: center !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
            }
            
            .print-format-ticket .print-w-2-3,
            .print-format-ticket .print-w-1-3 {
                width: 100% !important;
                text-align: center !important;
            }
            
            .print-format-ticket .text-right {
                text-align: left !important;
            }
            
            .print-format-ticket table th, 
            .print-format-ticket table td {
                padding: 3px 2px !important;
                font-size: 7.5px !important;
            }
            
            .print-format-ticket .border-t {
                padding-top: 6px !important;
                margin-top: 6px !important;
            }
            .print-format-ticket .text-lg {
                font-size: 11px !important;
            }
            .print-format-ticket .mt-10 {
                margin-top: 15px !important;
            }
            
            .no-print {
                display: none !important;
            }
            /* Grid layout print fallback */
            .print-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 1rem !important;
            }
            .print-flex {
                display: flex !important;
            }
            .print-justify-between {
                justify-content: space-between !important;
            }
            .print-w-2-3 {
                width: 66.666% !important;
            }
            .print-w-1-3 {
                width: 33.333% !important;
            }
        }
    </style>

    <!-- RECEIPT MODAL D'AFFICHAGE -->
    @if($showReceipt && count($receiptData) > 0)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm print-modal-container" x-data="{ printFormat: 'A4' }">
        <!-- Client-side Dynamic Page Size Handler -->
        <style x-text="`
            @media print {
                @page {
                    size: ${printFormat === 'A4' ? 'A4 portrait' : (printFormat === 'A5' ? 'A5 portrait' : '80mm auto')} !important;
                    margin: ${printFormat === 'ticket' ? '2mm' : '8mm'} !important;
                }
            }
        `"></style>
        <div class="bg-white dark:bg-[#0a0a0a] rounded-2xl w-full max-w-xl shadow-2xl border border-slate-200 dark:border-[#1a1a1a] overflow-hidden">
            
            <div class="p-6 border-b border-slate-100 dark:border-[#1a1a1a] flex justify-between items-center no-print">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">Aperçu du Reçu</h3>
                <div class="flex items-center gap-1.5 border border-slate-200 dark:border-[#333] rounded-lg p-0.5 bg-slate-50 dark:bg-[#111]">
                    <button type="button" @click="printFormat = 'A4'" :class="printFormat === 'A4' ? 'bg-white dark:bg-[#222] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'" class="px-2.5 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                        A4
                    </button>
                    <button type="button" @click="printFormat = 'A5'" :class="printFormat === 'A5' ? 'bg-white dark:bg-[#222] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'" class="px-2.5 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                        A5
                    </button>
                    <button type="button" @click="printFormat = 'ticket'" :class="printFormat === 'ticket' ? 'bg-white dark:bg-[#222] text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'" class="px-2.5 py-1 text-xs font-bold rounded-md transition-all flex items-center gap-1">
                        Ticket (80mm)
                    </button>
                </div>
                <button wire:click="closeReceipt" class="p-2 rounded-full text-slate-400 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- PRINTER FRIENDLY SECTION -->
            <div id="print-receipt-section" :class="printFormat === 'A4' ? 'print-format-a4' : (printFormat === 'A5' ? 'print-format-a5' : 'print-format-ticket')" class="p-8 bg-white text-slate-950">
                <div class="print-flex print-justify-between items-start border-b pb-6 mb-6">
                    <div class="print-w-2-3 print-logo-container flex items-center gap-4">
                        @if(auth()->user()->school?->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url(auth()->user()->school->logo) }}" class="w-12 h-12 rounded-xl object-cover print-logo" alt="Logo">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-indigo-600 print-logo-icon shrink-0">
                                <svg class="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <h2 class="text-lg font-bold tracking-tight uppercase text-slate-950">{{ auth()->user()->school?->name ?? 'YEBOCK SCHOOL' }}</h2>
                            <p class="text-[10px] text-slate-500 mt-0.5">{{ auth()->user()->school?->address ?? 'Kinshasa, République Démocratique du Congo' }}</p>
                            <p class="text-[10px] text-slate-500">{{ auth()->user()->school?->email ?? 'Contact: info@yebookschool.com' }}</p>
                        </div>
                    </div>
                    <div class="print-w-1-3 text-right">
                        <span class="inline-block px-2.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[9px] font-bold rounded-full uppercase tracking-wider">
                            {{ $receiptData['fee_name'] }}
                        </span>
                        <p class="text-[10px] text-slate-500 mt-2">N°: <strong class="text-slate-900">{{ $receiptData['receipt_no'] }}</strong></p>
                        <p class="text-[10px] text-slate-500">Date: {{ $receiptData['date'] }}</p>
                    </div>
                </div>

                <div class="print-grid mb-6">
                    <div>
                        <p class="text-[10px] uppercase text-slate-400 tracking-wider">Élève Concerné</p>
                        <p class="text-xs font-bold mt-1 text-slate-950">{{ $receiptData['student_name'] }}</p>
                        <p class="text-[10px] text-slate-600 mt-0.5">Matricule: <strong>{{ $receiptData['matricule'] }}</strong></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase text-slate-400 tracking-wider">Classe & Année</p>
                        <p class="text-xs font-bold mt-1 text-slate-950">{{ $receiptData['class'] }}</p>
                        <p class="text-[10px] text-slate-600 mt-0.5">Année Scolaire: {{ $receiptData['academic_year'] }}</p>
                    </div>
                </div>

                <div class="border rounded-lg overflow-hidden mb-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 border-b">
                                <th class="p-3 text-[10px] font-bold uppercase text-slate-500">Type de Versement</th>
                                <th class="p-3 text-[10px] font-bold uppercase text-right text-slate-500">Montant (USD)</th>
                                <th class="p-3 text-[10px] font-bold uppercase text-right text-slate-500">Montant (CDF)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="p-3 text-xs font-medium text-slate-900">{{ $receiptData['fee_name'] }}</td>
                                <td class="p-3 text-xs font-semibold text-right text-slate-900">${{ number_format($receiptData['amount'], 2) }}</td>
                                <td class="p-3 text-xs font-semibold text-right text-slate-900">{{ number_format($receiptData['amount_cdf'], 2) }} FC</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="print-flex print-justify-between items-start border-t pt-6">
                    <div class="text-[9px] text-slate-500 max-w-[65%]">
                        <p><strong>Note :</strong> Les paiements d'inscriptions ou de réinscriptions ne sont pas remboursables.</p>
                        <p class="mt-1 text-[9px] text-slate-400">Taux de change appliqué : 1 USD = {{ number_format($receiptData['exchange_rate'], 2) }} FC</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400">Total réglé</p>
                        <p class="text-lg font-black text-indigo-600 mt-0.5">${{ number_format($receiptData['amount'], 2) }}</p>
                        <p class="text-[10px] text-slate-500 font-semibold">{{ number_format($receiptData['amount_cdf'], 2) }} FC</p>
                    </div>
                </div>

                <div class="mt-10 print-flex print-justify-between text-[10px] text-slate-400 pt-4 border-t border-dashed">
                    <p>Signature Caissier</p>
                    <p>Cachet de l'École</p>
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 flex gap-4 no-print">
                <button type="button" onclick="window.print()" class="flex-1 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimer
                </button>
                <button type="button" wire:click="closeReceipt" class="flex-1 py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                    Fermer
                </button>
            </div>

        </div>
    </div>
    @endif

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
