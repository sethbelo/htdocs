<div class="space-y-6 animate-fade-in pb-12">
    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                <a href="{{ \App\Filament\Pages\ProAdmissions::getUrl() }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Admissions</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-indigo-600 dark:text-indigo-400">Profil Candidat</span>
            </div>
            <h1 class="text-3xl font-bold font-space text-slate-900 dark:text-white">Profil de {{ $admission->first_name }} {{ $admission->last_name }}</h1>
        </div>
        <div class="flex gap-3">
            <button type="button" class="px-4 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-[#1a1a1a] hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Imprimer
            </button>
            <a href="{{ \App\Filament\Pages\ProAdmissions::getUrl() }}" class="px-4 py-2 bg-slate-100 dark:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-slate-200 dark:hover:bg-[#222] transition-colors flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Retour
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-200 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- COLONNE GAUCHE (Résumé et Actions) -->
        <div class="space-y-6">
            <!-- Profil Card -->
            <div class="glass-panel overflow-hidden relative">
                <div class="h-24 w-full" style="background: linear-gradient(135deg, #{{ $admission->color ?: '3b82f6' }}44, #{{ $admission->color ?: '3b82f6' }}aa);"></div>
                <div class="px-6 pb-6 pt-0 relative flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-3xl -mt-12 flex items-center justify-center text-3xl font-bold text-white shadow-xl ring-4 ring-white dark:ring-[#0a0a0a] mb-4" 
                         style="background: linear-gradient(135deg, #{{ $admission->color ?: '3b82f6' }}, #{{ $admission->color ?: '3b82f6' }}cc);">
                         {{ $admission->initials }}
                    </div>
                    <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white">{{ $admission->first_name }} {{ $admission->last_name }}</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Candidat pour : <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $admission->requested_class }}</span></p>
                    
                    <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border
                          {{ $admission->status === 'En attente' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/40' : '' }}
                          {{ $admission->status === 'Entretien prévu' ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/40' : '' }}
                          {{ $admission->status === 'Accepté' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/40' : '' }}
                          {{ $admission->status === 'Rejeté' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-900/40' : '' }}
                          {{ $admission->status === 'Inscrit' ? 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/20 dark:text-indigo-400 dark:border-indigo-900/40' : '' }}">
                        {{ $admission->status }}
                    </div>
                </div>
            </div>

            <!-- Traitement Rapide / Inscription -->
            <div class="glass-panel p-6">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Décision & Traitement</h3>
                <div class="space-y-3">
                    @if($admission->status === 'Accepté')
                    <button wire:click="openEnrollmentModal" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-500/20 flex justify-center items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5"></i> Confirmer l'inscription
                    </button>
                    @endif

                    @if($admission->status === 'Inscrit')
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 rounded-xl text-center">
                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Élève Officiellement Inscrit</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Le matricule et les frais d'inscription ont été générés.</p>
                    </div>
                    @endif
                    
                    @if($admission->status !== 'Accepté' && $admission->status !== 'Inscrit')
                    <button wire:click="setStatus('Accepté')" class="w-full py-2.5 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 font-semibold rounded-xl border border-emerald-200 dark:border-emerald-900/40 hover:bg-emerald-100 transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> Accepter la demande
                    </button>
                    @endif
                    
                    @if($admission->status !== 'Entretien prévu' && $admission->status !== 'Inscrit')
                    <button wire:click="setStatus('Entretien prévu')" class="w-full py-2.5 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 font-semibold rounded-xl border border-blue-200 dark:border-blue-900/40 hover:bg-blue-100 transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="calendar" class="w-5 h-5"></i> Programmer un entretien
                    </button>
                    @endif

                    @if($admission->status !== 'Rejeté' && $admission->status !== 'Inscrit')
                    <button wire:click="setStatus('Rejeté')" class="w-full py-2.5 bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400 font-semibold rounded-xl border border-rose-200 dark:border-rose-900/40 hover:bg-rose-100 transition-colors flex justify-center items-center gap-2">
                        <i data-lucide="x-circle" class="w-5 h-5"></i> Rejeter la demande
                    </button>
                    @endif
                </div>
            </div>
            
            <!-- Contact Rapide -->
            <div class="glass-panel p-6">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Contact rapide</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-slate-50 dark:bg-[#111] rounded-lg text-slate-500">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-0.5">Candidat</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $admission->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-slate-50 dark:bg-[#111] rounded-lg text-slate-500">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-0.5">Candidat</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $admission->phone ?: 'Non renseigné' }}</p>
                        </div>
                    </div>
                    @if($admission->parent_phone)
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="phone-call" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-0.5">Parent ({{ $admission->parent_name }})</p>
                            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $admission->parent_phone }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE (Détails) -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="glass-panel p-6 md:p-8">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i data-lucide="user" class="w-5 h-5 text-indigo-500"></i> Informations Personnelles
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Date de naissance</p>
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ $admission->date_of_birth ? \Carbon\Carbon::parse($admission->date_of_birth)->format('d/m/Y') : 'Non renseigné' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Genre</p>
                        <p class="font-medium text-slate-900 dark:text-white">
                            @if($admission->gender === 'M') Masculin @elseif($admission->gender === 'F') Féminin @else Non renseigné @endif
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Adresse complète</p>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $admission->address ?: 'Non renseignée' }}</p>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-6 md:p-8">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i data-lucide="graduation-cap" class="w-5 h-5 text-indigo-500"></i> Parcours Scolaire
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Classe demandée</p>
                        <p class="font-bold text-indigo-600 dark:text-indigo-400 text-lg">{{ $admission->requested_class }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">École de provenance</p>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $admission->previous_school ?: 'Non renseigné' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Score d'évaluation</p>
                        <div class="flex items-center gap-4 mt-2">
                            <div class="flex-1 h-3 bg-slate-100 dark:bg-[#111] rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full relative" style="width: {{ $admission->score }}%">
                                    <div class="absolute inset-0 bg-white/20 w-full h-full" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);"></div>
                                </div>
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $admission->score }}/100</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass-panel p-6">
                    <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-indigo-500"></i> Responsable Légal
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Nom complet</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $admission->parent_name ?: 'Non renseigné' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">Email</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $admission->parent_email ?: 'Non renseigné' }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-panel p-6">
                    <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <i data-lucide="heart-pulse" class="w-5 h-5 text-rose-500"></i> Notes Médicales
                    </h3>
                    @if($admission->medical_notes)
                        <div class="p-4 bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/20 rounded-xl">
                            <p class="text-sm text-rose-700 dark:text-rose-300 leading-relaxed">{{ $admission->medical_notes }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400 italic">Aucune note médicale ou allergie signalée.</p>
                    @endif
                </div>
            </div>
            
            <div class="glass-panel p-6">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-5 h-5 text-indigo-500"></i> Documents joints
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Maquette de documents -->
                    <div class="p-4 border border-slate-200 dark:border-[#333] rounded-xl flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors cursor-pointer">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                            <i data-lucide="file-text" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">Dernier_bulletin.pdf</p>
                            <p class="text-xs text-slate-500">1.2 MB</p>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 dark:border-[#333] rounded-xl flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors cursor-pointer">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i data-lucide="image" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">Photo_identite.jpg</p>
                            <p class="text-xs text-slate-500">800 KB</p>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 dark:border-[#333] rounded-xl flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-[#111] transition-colors cursor-pointer opacity-50 border-dashed">
                        <div class="p-2 bg-slate-50 text-slate-400 rounded-lg">
                            <i data-lucide="upload" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-500">Ajouter...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- STYLE SPECIFIC TO PRINTING THE RECEIPT -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            .print-modal-container, .print-modal-container * {
                visibility: visible !important;
            }
            #print-receipt-section, #print-receipt-section * {
                visibility: visible !important;
            }
            #print-receipt-section {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                color: black !important;
                box-shadow: none !important;
                border: none !important;
                z-index: 99999 !important;
            }
            .print-modal-container {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                backdrop-filter: none !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            .print-modal-container > div {
                box-shadow: none !important;
                border: none !important;
                background: white !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- 1. MODAL CONFIRMATION INSCRIPTION -->
    <div x-show="$wire.isEnrollmentModalOpen" class="fixed inset-0 z-50 overflow-hidden" x-cloak
         x-data="{
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
                     alert('Impossible d\'accéder à la webcam : ' + err.message);
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
         }" x-init="$watch('$wire.isEnrollmentModalOpen', value => { if (!value) stopWebcam(); })">
        
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="$wire.isEnrollmentModalOpen = false"></div>
        
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-0">
            <div class="w-screen max-w-lg pointer-events-auto">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#1a1a1a]">
                    
                    <div class="px-6 py-6 border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 relative">
                        <button @click="$wire.isEnrollmentModalOpen = false" class="absolute top-6 right-6 p-2 rounded-full text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <h2 class="text-xl font-bold font-space text-slate-900 dark:text-white mt-2">Confirmer l'Inscription</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Élève : {{ $admission->first_name }} {{ $admission->last_name }}</p>
                    </div>

                    <div class="p-6 flex-1">
                        <form wire:submit.prevent="confirmEnrollment" class="space-y-6">
                            
                            <!-- Matricule unique -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Matricule (Généré automatiquement)</label>
                                <input type="text" wire:model="enroll_matricule" readonly class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-slate-100 dark:bg-[#111] text-slate-500 dark:text-slate-400 focus:outline-none cursor-not-allowed">
                            </div>

                            <!-- Année Scolaire -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Année Scolaire</label>
                                <select wire:model.live="enroll_academic_year_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Classe Affectée -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 font-semibold">Classe d'Affectation</label>
                                <select wire:model="enroll_class_id" class="block w-full py-2.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] text-slate-950 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                    @foreach($schoolClasses as $class)
                                        <option value="{{ $class['id'] }}">{{ $class['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Photo / Avatar (Upload ou Webcam) -->
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Photo de l'élève (Upload ou Caméra)</label>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Upload classique -->
                                    <div class="p-4 border border-dashed border-slate-200 dark:border-[#333] rounded-xl text-center flex flex-col items-center justify-center">
                                        <i data-lucide="upload" class="w-6 h-6 text-slate-400 mb-2"></i>
                                        <label class="cursor-pointer text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Téléverser un fichier
                                            <input type="file" wire:model="enroll_avatar_upload" class="hidden" accept="image/*">
                                        </label>
                                        @if ($enroll_avatar_upload)
                                            <span class="text-[10px] text-emerald-500 mt-1">Image chargée</span>
                                        @endif
                                    </div>

                                    <!-- Webcam capture -->
                                    <div class="p-4 border border-dashed border-slate-200 dark:border-[#333] rounded-xl text-center flex flex-col items-center justify-center">
                                        <i data-lucide="camera" class="w-6 h-6 text-slate-400 mb-2"></i>
                                        <button type="button" @click="initWebcam()" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Prendre via Webcam
                                        </button>
                                        <template x-if="capturedImage">
                                            <span class="text-[10px] text-emerald-500 mt-1">Photo capturée</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Webcam preview inline modal -->
                                <div x-show="cameraActive" class="p-3 bg-slate-900 rounded-xl space-y-3">
                                    <video x-ref="video" class="w-full rounded-lg bg-black h-36 object-cover" autoplay playsinline></video>
                                    <canvas x-ref="canvas" class="hidden"></canvas>
                                    <div class="flex gap-2">
                                        <button type="button" @click="capturePhoto()" class="flex-1 py-1 px-3 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700">Capturer</button>
                                        <button type="button" @click="stopWebcam()" class="py-1 px-3 bg-slate-700 text-white rounded-lg text-xs font-semibold hover:bg-slate-600">Annuler</button>
                                    </div>
                                </div>

                                <!-- Previews of photo -->
                                @if($enroll_avatar_upload)
                                    <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-[#111] rounded-lg">
                                        <img src="{{ $enroll_avatar_upload->temporaryUrl() }}" class="w-12 h-12 rounded-lg object-cover">
                                        <span class="text-xs text-slate-500">Aperçu du fichier</span>
                                    </div>
                                @endif
                                <template x-if="capturedImage">
                                    <div class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-[#111] rounded-lg">
                                        <img :src="capturedImage" class="w-12 h-12 rounded-lg object-cover">
                                        <span class="text-xs text-slate-500">Aperçu Webcam</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Frais d'inscription à valider -->
                            <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex gap-3">
                                <i data-lucide="info" class="w-5 h-5 text-amber-500 shrink-0"></i>
                                <div>
                                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-400">Paiement automatique des Frais</p>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">Cette action va générer automatiquement un paiement comptable de 150 USD pour les Frais d'Inscription.</p>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100 dark:border-[#1a1a1a] flex flex-col sm:flex-row gap-3">
                                <button type="button" @click="$wire.isEnrollmentModalOpen = false" class="py-2.5 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                    Annuler
                                </button>
                                <button type="button" wire:click="confirmEnrollment(false)" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-[#111] dark:hover:bg-[#1a1a1a] text-slate-700 dark:text-slate-300 font-semibold rounded-xl transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Valider uniquement
                                </button>
                                <button type="button" wire:click="confirmEnrollment(true)" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="printer" class="w-4 h-4"></i> Valider & Imprimer le reçu
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
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

    <!-- 2. REÇU DE PAIEMENT PRINTABLE MODAL -->
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

            <!-- RECEIPT AREA TO PRINT -->
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
