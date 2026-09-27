<div class="h-full flex flex-col p-6 max-w-7xl mx-auto font-sans">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mon Profil</h1>
        <p class="text-slate-500 text-sm mt-1">Gérez vos informations personnelles et complétez votre dossier professionnel (CV et Dossier Académique).</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 rounded-2xl text-sm border border-emerald-200/50 dark:border-emerald-800/30 flex items-center gap-2 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <form wire:submit.prevent="saveProfile" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Pane: Basic User Profile Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Details Card -->
            <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-3xl p-6 shadow-sm space-y-6">
                <h3 class="text-lg font-bold font-space bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500">
                    Informations Personnelles
                </h3>
                
                <!-- Avatar Setup -->
                <div class="flex flex-col sm:flex-row items-center gap-5 pb-6 border-b border-slate-100 dark:border-[#161616]">
                    <div class="relative group">
                        @if ($avatar_upload)
                            <img src="{{ $avatar_upload->temporaryUrl() }}" class="w-24 h-24 rounded-full object-cover border-4 border-indigo-500 shadow-lg">
                        @elseif ($avatar)
                            <img src="{{ Storage::url($avatar) }}" class="w-24 h-24 rounded-full object-cover border-4 border-slate-100 dark:border-[#222] shadow-md">
                        @else
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-3xl shadow-md">
                                {{ strtoupper(substr($name, 0, 2)) }}
                            </div>
                        @endif

                        <!-- Photo upload label overlay -->
                        <label for="avatar-input" class="absolute inset-0 bg-black/40 hover:bg-black/60 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </label>
                        <input type="file" id="avatar-input" wire:model="avatar_upload" class="hidden" accept="image/*">
                    </div>
                    
                    <div class="text-center sm:text-left space-y-1">
                        <h4 class="font-bold text-slate-800 dark:text-slate-200">Avatar du Profil</h4>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Mettez en ligne un fichier JPEG, PNG ou GIF. Max 2MB.</p>
                        @error('avatar_upload') 
                            <p class="text-xs text-rose-500 font-semibold mt-1 animate-pulse">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Input Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nom Complet</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium">
                        @error('name') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Adresse Email</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium">
                        @error('email') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <style>
                            .iti { width: 100% !important; }
                        </style>
                        <div x-data="{
                            iti: null,
                            initIti() {
                                this.iti = window.intlTelInput(this.$refs.phoneInput, {
                                    initialCountry: 'cd',
                                    preferredCountries: ['cd', 'fr', 'be', 'ca'],
                                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js'
                                });
                                
                                const updatePhone = () => {
                                    const number = this.iti.getNumber();
                                    $wire.set('phone', number ? number : this.$refs.phoneInput.value);
                                };
                                this.$refs.phoneInput.addEventListener('blur', updatePhone);
                                this.$refs.phoneInput.addEventListener('change', updatePhone);
                                this.$refs.phoneInput.addEventListener('countrychange', updatePhone);
                            }
                        }" x-init="initIti()" wire:ignore>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Numéro de Téléphone</label>
                            <div class="w-full">
                                <input 
                                    x-ref="phoneInput"
                                    type="tel" 
                                    class="w-full px-4 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium"
                                    value="{{ $phone }}"
                                    style="width: 100% !important;"
                                >
                            </div>
                        </div>
                        @error('phone') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Rôle / Type de Compte</label>
                        <input type="text" value="{{ strtoupper(Auth::user()->type) }}" disabled class="w-full px-4 py-2.5 bg-slate-100 dark:bg-[#111] border border-slate-200/50 dark:border-[#1a1a1a] rounded-xl text-slate-400 dark:text-slate-600 font-bold uppercase tracking-wider text-xs cursor-not-allowed">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Adresse Complète</label>
                        <textarea wire:model="address" rows="3" class="w-full px-4 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium" placeholder="Ex: Av. de la Libération, Kinshasa/Gombe..."></textarea>
                        @error('address') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-3xl p-6 shadow-sm space-y-5" x-data="{ showPass: false, showConfirmPass: false }">
                <h3 class="text-lg font-bold font-space bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500">
                    Sécurité & Mot de Passe
                </h3>
                <p class="text-xs text-slate-400 dark:text-slate-500">Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe actuel.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nouveau Mot de Passe</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" wire:model="password" class="w-full pl-4 pr-10 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium">
                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password') <p class="text-xs text-rose-500 mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Confirmer le Mot de Passe</label>
                        <div class="relative">
                            <input :type="showConfirmPass ? 'text' : 'password'" wire:model="password_confirmation" class="w-full pl-4 pr-10 py-2.5 bg-slate-50/50 dark:bg-[#141414] border border-slate-200 dark:border-[#222] rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 dark:text-slate-100 transition-all text-sm font-medium">
                            <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                                <svg x-show="!showConfirmPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showConfirmPass" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Pane: Completed Profile Documents (CV & Academic Dossier) -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-[#0c0c0c] border border-slate-200 dark:border-[#1a1a1a] rounded-3xl p-6 shadow-sm space-y-6 h-full flex flex-col">
                <h3 class="text-lg font-bold font-space bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500">
                    Dossier Professionnel
                </h3>

                <!-- Document 1: Curriculum Vitae (CV) -->
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Curriculum Vitae (CV)</label>

                    @if ($cv_upload)
                        <!-- Newly Uploaded State preview -->
                        <div class="p-3 bg-indigo-50/40 dark:bg-indigo-950/10 border border-dashed border-indigo-400 rounded-2xl flex items-center justify-between text-xs animate-slide-in">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span class="truncate font-semibold text-indigo-700 dark:text-indigo-400 max-w-[150px]">{{ $cv_upload->getClientOriginalName() }}</span>
                            </div>
                            <span class="text-[10px] text-indigo-500 font-bold">Nouveau (Non sauvegardé)</span>
                        </div>
                    @elseif ($cv_path)
                        <!-- Existing Document layout -->
                        <div class="p-4 bg-slate-50 dark:bg-[#141414] border border-slate-200/50 dark:border-[#222] rounded-2xl flex flex-col gap-3 shadow-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="overflow-hidden flex-1">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">Curriculum_Vitae.pdf</p>
                                    <p class="text-[9px] text-slate-400">Document PDF enregistré</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ Storage::url($cv_path) }}" target="_blank" class="flex-1 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-[#1f1f1f] dark:hover:bg-[#2a2a2a] text-slate-700 dark:text-slate-300 rounded-lg text-center text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                                    Voir le document
                                </a>
                                <button type="button" wire:click="deleteDocument('cv')" wire:confirm="Confirmez-vous la suppression de votre CV ?" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Upload Dropzone layout -->
                        <div class="relative border-2 border-dashed border-slate-200 dark:border-[#222] rounded-2xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors bg-slate-50/50 dark:bg-[#111]/30">
                            <label class="flex flex-col items-center justify-center p-6 cursor-pointer space-y-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Téléverser votre CV</span>
                                <span class="text-[9px] text-slate-400">Format PDF uniquement (Max 10Mo)</span>
                                <input type="file" wire:model="cv_upload" class="hidden" accept="application/pdf">
                            </label>
                        </div>
                    @endif
                    
                    @error('cv_upload') <p class="text-xs text-rose-500 font-semibold animate-pulse">{{ $message }}</p> @enderror
                </div>

                <!-- Document 2: Academic Dossier (Dossier Académique) -->
                <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-[#161616]">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">Dossier Académique</label>

                    @if ($dossier_upload)
                        <!-- Newly Uploaded State preview -->
                        <div class="p-3 bg-indigo-50/40 dark:bg-indigo-950/10 border border-dashed border-indigo-400 rounded-2xl flex items-center justify-between text-xs animate-slide-in">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span class="truncate font-semibold text-indigo-700 dark:text-indigo-400 max-w-[150px]">{{ $dossier_upload->getClientOriginalName() }}</span>
                            </div>
                            <span class="text-[10px] text-indigo-500 font-bold">Nouveau (Non sauvegardé)</span>
                        </div>
                    @elseif ($dossier_academique_path)
                        <!-- Existing Document layout -->
                        <div class="p-4 bg-slate-50 dark:bg-[#141414] border border-slate-200/50 dark:border-[#222] rounded-2xl flex flex-col gap-3 shadow-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="overflow-hidden flex-1">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">Dossier_Academique.pdf</p>
                                    <p class="text-[9px] text-slate-400">Document PDF enregistré</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ Storage::url($dossier_academique_path) }}" target="_blank" class="flex-1 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-[#1f1f1f] dark:hover:bg-[#2a2a2a] text-slate-700 dark:text-slate-300 rounded-lg text-center text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                                    Voir le document
                                </a>
                                <button type="button" wire:click="deleteDocument('dossier')" wire:confirm="Confirmez-vous la suppression de votre dossier académique ?" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Upload Dropzone layout -->
                        <div class="relative border-2 border-dashed border-slate-200 dark:border-[#222] rounded-2xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors bg-slate-50/50 dark:bg-[#111]/30">
                            <label class="flex flex-col items-center justify-center p-6 cursor-pointer space-y-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Téléverser votre dossier</span>
                                <span class="text-[9px] text-slate-400">Format PDF uniquement (Max 10Mo)</span>
                                <input type="file" wire:model="dossier_upload" class="hidden" accept="application/pdf">
                            </label>
                        </div>
                    @endif
                    
                    @error('dossier_upload') <p class="text-xs text-rose-500 font-semibold animate-pulse">{{ $message }}</p> @enderror
                </div>

                <!-- Loading State indicators for file uploads -->
                <div wire:loading wire:target="avatar_upload, cv_upload, dossier_upload" class="bg-indigo-50/50 dark:bg-indigo-950/10 p-3 rounded-2xl text-[10px] text-indigo-600 dark:text-indigo-400 flex items-center justify-center gap-2 mt-auto border border-indigo-100/50 dark:border-indigo-950/20 shadow-sm animate-pulse">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>Envoi en cours, veuillez patienter...</span>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 mt-auto">
                    <button type="submit" class="w-full py-3 bg-gradient-to-br from-indigo-500 to-purple-600 border-none text-white rounded-2xl hover:opacity-95 text-sm font-bold shadow-md shadow-indigo-500/10 flex items-center justify-center gap-2 transition-all hover:scale-[1.01] active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Enregistrer le Profil
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
