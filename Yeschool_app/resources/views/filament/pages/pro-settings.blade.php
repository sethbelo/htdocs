<div class="space-y-6 animate-fade-in pb-12">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 text-sm text-slate-500 dark:text-slate-400 font-medium">
                <a href="{{ \App\Filament\Pages\ProDashboard::getUrl() }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-indigo-600 dark:text-indigo-400">Administration</span>
            </div>
            <h1 class="text-3xl font-bold font-space text-slate-900 dark:text-white">Configuration</h1>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="glass-panel overflow-hidden">
        <div class="border-b border-slate-100 dark:border-[#1a1a1a] bg-slate-50/50 dark:bg-[#111]/50 px-2 sm:px-4 flex overflow-x-auto hide-scrollbar">
            
            <button wire:click="switchTab('general')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'general' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="building-2" class="w-4 h-4"></i> Général
                </div>
                @if($activeTab === 'general')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('localisation')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'localisation' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="globe" class="w-4 h-4"></i> Localisation
                </div>
                @if($activeTab === 'localisation')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('documents')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'documents' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Documents
                </div>
                @if($activeTab === 'documents')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('security')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'security' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4"></i> Sécurité & Accès
                </div>
                @if($activeTab === 'security')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('server')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'server' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="server" class="w-4 h-4"></i> Système & Serveur
                </div>
                @if($activeTab === 'server')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('currency')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'currency' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="banknote" class="w-4 h-4"></i> Devises & Taux
                </div>
                @if($activeTab === 'currency')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

            <button wire:click="switchTab('campuses')" class="relative px-4 py-4 text-sm font-medium whitespace-nowrap transition-colors {{ $activeTab === 'campuses' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}">
                <div class="flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4"></i> Succursales
                </div>
                @if($activeTab === 'campuses')
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-t-full"></div>
                @endif
            </button>

        </div>

        <!-- TAB CONTENT -->
        <div class="p-6 sm:p-8 min-h-[400px]">
            
            <!-- GENERAL TAB -->
            @if($activeTab === 'general')
            <div class="animate-fade-in max-w-3xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Identité de l'Établissement</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gérez les informations publiques de votre école.</p>
                </div>

                <form wire:submit.prevent="saveGeneral" class="space-y-6">
                    
                    <!-- Logo Upload -->
                    <div class="flex items-center gap-6 p-5 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50 dark:bg-[#0a0a0a]">
                        <div class="w-20 h-20 rounded-full bg-white dark:bg-[#111] border border-slate-200 dark:border-[#333] flex items-center justify-center overflow-hidden shadow-sm">
                            <i data-lucide="image" class="w-8 h-8 text-slate-300 dark:text-slate-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Logo de l'établissement</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Format recommandé : PNG transparent, 256x256px.</p>
                            <button type="button" class="px-3 py-1.5 text-xs font-medium bg-white dark:bg-[#111] border border-slate-200 dark:border-[#333] rounded-lg shadow-sm hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition-all">
                                Uploader une image
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom de l'établissement</label>
                            <input type="text" wire:model="school_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Numéro ID / SIRET</label>
                            <input type="text" wire:model="school_id_number" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description courte</label>
                            <textarea wire:model="school_description" rows="2" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email de contact</label>
                            <input type="email" wire:model="school_email" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Téléphone</label>
                            <input type="text" wire:model="school_phone" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Province (RDC)</label>
                            <input type="text" wire:model="school_province" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ville (RDC)</label>
                            <input type="text" wire:model="school_ville" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Commune / Territoire (RDC)</label>
                            <input type="text" wire:model="school_commune" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Code École (6 chiffres)</label>
                            <input type="text" maxlength="6" wire:model="school_code_ecole" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse postale</label>
                            <textarea wire:model="school_address" rows="2" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"></textarea>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
            @endif


            <!-- LOCALISATION TAB -->
            @if($activeTab === 'localisation')
            <div class="animate-fade-in max-w-3xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Localisation & Affichage</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Réglez la monnaie, l'heure et l'apparence par défaut du système.</p>
                </div>

                <form wire:submit.prevent="saveLocalisation" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="md:col-span-2 p-5 border border-indigo-100 dark:border-indigo-900/30 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl">
                            <label class="block text-sm font-semibold text-indigo-900 dark:text-indigo-300 mb-3">Thème par défaut du SIGS</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model="default_theme" value="light" class="peer sr-only">
                                    <div class="p-4 text-center rounded-xl border border-slate-200 bg-white peer-checked:border-indigo-500 peer-checked:ring-1 peer-checked:ring-indigo-500 transition-all">
                                        <i data-lucide="sun" class="w-6 h-6 mx-auto mb-2 text-amber-500"></i>
                                        <span class="text-sm font-medium text-slate-700">Clair</span>
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model="default_theme" value="dark" class="peer sr-only">
                                    <div class="p-4 text-center rounded-xl border border-[#333] bg-[#0a0a0a] peer-checked:border-indigo-500 peer-checked:ring-1 peer-checked:ring-indigo-500 transition-all">
                                        <i data-lucide="moon" class="w-6 h-6 mx-auto mb-2 text-indigo-400"></i>
                                        <span class="text-sm font-medium text-slate-300">Sombre</span>
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" wire:model="default_theme" value="system" class="peer sr-only">
                                    <div class="p-4 text-center rounded-xl border border-slate-200 dark:border-[#333] bg-slate-50 dark:bg-[#111] peer-checked:border-indigo-500 peer-checked:ring-1 peer-checked:ring-indigo-500 transition-all">
                                        <i data-lucide="monitor" class="w-6 h-6 mx-auto mb-2 text-slate-500"></i>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Système</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Devise principale</label>
                            <select wire:model.live="currency" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                @php
                                    $allCurrencies = \App\Models\Currency::where('is_active', true)->orderBy('code')->get();
                                @endphp
                                @if($allCurrencies->count() > 0)
                                    @foreach($allCurrencies as $dbCurr)
                                        <option value="{{ $dbCurr->code }}">{{ $dbCurr->code }} - {{ $dbCurr->name }} ({{ $dbCurr->symbol ?: $dbCurr->code }})</option>
                                    @endforeach
                                @else
                                    <option value="EUR">Euro (€)</option>
                                    <option value="XOF">FCFA (CFA)</option>
                                    <option value="USD">Dollar ($)</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Taux de change (Optionnel)</label>
                            <input type="text" wire:model="exchange_rate" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Format de la date</label>
                            <select wire:model="date_format" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="d/m/Y">JJ/MM/AAAA (07/07/2026)</option>
                                <option value="m/d/Y">MM/JJ/AAAA (07/07/2026)</option>
                                <option value="Y-m-d">AAAA-MM-JJ (2026-07-07)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Format de l'heure</label>
                            <select wire:model="time_format" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                <option value="H:i">24 Heures (14:30)</option>
                                <option value="h:i A">12 Heures (02:30 PM)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
            @endif


            <!-- DOCUMENTS TAB -->
            @if($activeTab === 'documents')
            <div class="animate-fade-in max-w-3xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Documents Scolaires</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Personnalisez l'en-tête et le pied de page des bulletins et reçus.</p>
                </div>

                <form wire:submit.prevent="saveDocuments" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texte Haut de page (En-tête)</label>
                        <textarea wire:model="doc_header_text" rows="3" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all" placeholder="Ex: République de... / Ministère de l'Éducation..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Texte Bas de page (Pied de page)</label>
                        <textarea wire:model="doc_footer_text" rows="3" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all" placeholder="Ex: Bulletin généré le... / Signature du Directeur..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- SECURITY TAB -->
            @if($activeTab === 'security')
            <div class="animate-fade-in max-w-5xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Sécurité & Accès</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gérez les utilisateurs, rôles, permissions et la sécurité globale.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Security Settings -->
                    <div class="lg:col-span-1 space-y-6">
                        <form wire:submit.prevent="saveSecurity" class="p-5 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50 dark:bg-[#0a0a0a]">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4">
                                <i data-lucide="lock" class="w-4 h-4 text-indigo-500"></i> Contrôle Mot de passe
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Complexité exigée</label>
                                    <select wire:model="password_complexity" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#111] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                        <option value="low">Faible (8 caractères min.)</option>
                                        <option value="medium">Moyenne (Chiffres + Lettres)</option>
                                        <option value="high">Forte (Majuscule + Symbole)</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-semibold rounded-xl hover:bg-slate-800 dark:hover:bg-slate-200 transition-all">
                                    Enregistrer
                                </button>
                            </div>
                        </form>

                        <div class="p-5 border border-rose-100 dark:border-rose-900/30 rounded-2xl bg-rose-50/50 dark:bg-rose-900/10">
                            <h3 class="text-sm font-bold text-rose-900 dark:text-rose-400 mb-2">Zone Dangereuse</h3>
                            <p class="text-xs text-rose-700 dark:text-rose-300 mb-4">Réinitialiser toute la base de données ou supprimer toutes les archives.</p>
                            <button type="button" class="w-full py-2 bg-white dark:bg-[#111] border border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400 text-sm font-semibold rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all">
                                Mode Maintenance
                            </button>
                        </div>
                    </div>

                    <!-- Right: Users Management -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Utilisateurs du Système</h3>
                        </div>

                        @if (session()->has('success_users'))
                            <div class="p-3 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-xl text-xs mb-4">
                                {{ session('success_users') }}
                            </div>
                        @endif
                        @if (session()->has('error_users'))
                            <div class="p-3 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30 rounded-xl text-xs mb-4">
                                {{ session('error_users') }}
                            </div>
                        @endif

                        @if($editing_user_id)
                            <!-- Edit User Form -->
                            <form wire:submit.prevent="updateUser" class="mb-6 p-4 border border-amber-100 dark:border-amber-900/30 rounded-xl bg-amber-50/30 dark:bg-amber-900/10 flex flex-wrap gap-3 items-end animate-fade-in">
                                <div class="flex-1 min-w-[180px]">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Modifier le Nom</label>
                                    <input type="text" wire:model="editing_user_name" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div class="flex-1 min-w-[180px]">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                    <input type="email" wire:model="editing_user_email" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nouveau mot de passe</label>
                                    <input type="password" wire:model="editing_user_password" placeholder="Optionnel" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text                                <!-- Odoo-style Security Matrix -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full border-t border-slate-100 dark:border-[#222] pt-3 mt-1">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Scolarité (Académique)</label>
                                        <select wire:model="editing_user_academic_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Enseignant)</option>
                                            <option value="manager">Responsable (Directeur/Préfet)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Finances & Comptabilité</label>
                                        <select wire:model="editing_user_finance_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Caissier)</option>
                                            <option value="manager">Responsable (Comptable)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Ressources Humaines</label>
                                        <select wire:model="editing_user_hr_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Collaborateur)</option>
                                            <option value="manager">Responsable (RH)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Administration Système</label>
                                        <select wire:model="editing_user_admin_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="manager">Administrateur (Total)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex gap-2 w-full justify-end border-t border-slate-100 dark:border-[#222] pt-3 mt-1">
                                    <button type="submit" class="py-1.5 px-4 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 shadow-sm transition-all h-[34px]">
                                        Enregistrer
                                    </button>
                                    <button type="button" wire:click="cancelEditUser" class="py-1.5 px-3 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-lg border border-slate-200 dark:border-[#333] hover:bg-slate-50 transition-all h-[34px]">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        @else
                            <!-- Add User Form -->
                            <form wire:submit.prevent="createUser" class="mb-6 p-4 border border-indigo-100 dark:border-indigo-900/30 rounded-xl bg-indigo-50/30 dark:bg-indigo-900/10 flex flex-wrap gap-3 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nom complet</label>
                                    <input type="text" wire:model="new_user_name" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                    <input type="email" wire:model="new_user_email" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Mot de passe</label>
                                    <input type="password" wire:model="new_user_password" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <!-- Odoo-style Security Matrix -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full border-t border-slate-100 dark:border-[#222]/30 pt-3 mt-1">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Scolarité (Académique)</label>
                                        <select wire:model="new_user_academic_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Enseignant)</option>
                                            <option value="manager">Responsable (Directeur/Préfet)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Finances & Comptabilité</label>
                                        <select wire:model="new_user_finance_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Caissier)</option>
                                            <option value="manager">Responsable (Comptable)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Ressources Humaines</label>
                                        <select wire:model="new_user_hr_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="user">Utilisateur (Collaborateur)</option>
                                            <option value="manager">Responsable (RH)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Administration Système</label>
                                        <select wire:model="new_user_admin_level" class="block w-full py-1.5 px-2.5 text-xs border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                            <option value="none">Aucun</option>
                                            <option value="manager">Administrateur (Total)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="w-full flex justify-end">
                                    <button type="submit" class="py-1.5 px-4 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition-all h-[34px]">
                                        Ajouter
                                    </button>
                                </div>
                            </form>
                        @endif
 
                        <!-- Users Table -->
                        <div class="border border-slate-200 dark:border-[#333] rounded-xl overflow-hidden">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Utilisateur</th>
                                        <th class="px-4 py-3 font-semibold">Droits & Rôles Actifs (Odoo-style)</th>
                                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                                    @foreach($users as $user)
                                    @php
                                        $dbUser = \App\Models\User::find($user['id']);
                                        $rolesList = $dbUser ? $dbUser->roles->pluck('name')->toArray() : [];
                                    @endphp
                                    <tr class="hover:bg-slate-50 dark:hover:bg-[#111] transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs uppercase">
                                                    {{ substr($user['name'], 0, 2) }}
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-900 dark:text-white">{{ $user['name'] }}</div>
                                                    <div class="text-xs text-slate-500">{{ $user['email'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($rolesList as $role)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30">
                                                        {{ $role }}
                                                    </span>
                                                @empty
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-500 border border-slate-200 dark:bg-slate-900/30 dark:text-slate-400 dark:border-slate-800">
                                                        Aucun droit
                                                    </span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="editUser({{ $user['id'] }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Modifier">
                                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                </button>
                                                @if(auth()->id() != $user['id'])
                                                <button type="button" wire:click="deleteUser({{ $user['id'] }})" wire:confirm="Êtes-vous sûr de vouloir supprimer cet utilisateur ?" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Supprimer">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            @endif

            <!-- SYSTEM & SERVER TAB -->
            @if($activeTab === 'server')
            <div class="animate-fade-in max-w-5xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Système & Serveur</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configurez les variables d'environnement (.env) et les paramètres système de l'application.</p>
                </div>

                @if (session()->has('success_server'))
                    <div class="p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-xl text-sm flex items-center gap-3 mb-6">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        {{ session('success_server') }}
                    </div>
                @endif

                <form wire:submit.prevent="saveSystemAndEnv" class="space-y-8">
                    <!-- SECTION 1: APP CONFIG -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="app-window" class="w-5 h-5 text-indigo-500"></i> Configuration Application (APP_)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom de l'application (APP_NAME)</label>
                                <input type="text" wire:model="app_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Environnement (APP_ENV)</label>
                                <select wire:model="app_env" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="local">Local / Développement</option>
                                    <option value="testing">Testing / Tests</option>
                                    <option value="production">Production</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL de l'application (APP_URL)</label>
                                <input type="text" wire:model="app_url" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div class="flex items-center gap-3 h-full pt-6">
                                <input type="checkbox" wire:model="app_debug" id="appDebugCheck" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                <label for="appDebugCheck" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Activer le mode Débogage (APP_DEBUG)</label>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 1.5: DATABASE CONFIG -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="database" class="w-5 h-5 text-indigo-500"></i> Configuration Base de Données (DB_)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Connexion (DB_CONNECTION)</label>
                                <select wire:model="db_connection" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="sqlite">SQLite</option>
                                    <option value="mysql">MySQL</option>
                                    <option value="mariadb">MariaDB</option>
                                    <option value="pgsql">PostgreSQL</option>
                                    <option value="oracle">Oracle</option>
                                    <option value="sqlsrv">SQL Server</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hôte (DB_HOST)</label>
                                <input type="text" wire:model="db_host" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port (DB_PORT)</label>
                                <input type="text" wire:model="db_port" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Base de données (DB_DATABASE)</label>
                                <input type="text" wire:model="db_database" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Utilisateur (DB_USERNAME)</label>
                                <input type="text" wire:model="db_username" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mot de passe (DB_PASSWORD)</label>
                                <input type="password" wire:model="db_password" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: MAIL CONFIG -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="mail" class="w-5 h-5 text-indigo-500"></i> Serveur de Messagerie (MAIL_)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pilote Mail (MAIL_MAILER)</label>
                                <select wire:model="mail_mailer" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="smtp">SMTP</option>
                                    <option value="log">Log (Fichier journal local)</option>
                                    <option value="sendmail">Sendmail</option>
                                    <option value="ses">AWS SES</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hôte SMTP (MAIL_HOST)</label>
                                <input type="text" wire:model="mail_host" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port SMTP (MAIL_PORT)</label>
                                <input type="text" wire:model="mail_port" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Identifiant SMTP (MAIL_USERNAME)</label>
                                <input type="text" wire:model="mail_username" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mot de passe SMTP (MAIL_PASSWORD)</label>
                                <input type="password" wire:model="mail_password" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cryptage (MAIL_ENCRYPTION)</label>
                                <select wire:model="mail_encryption" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="">Aucun</option>
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Adresse Expéditeur (MAIL_FROM_ADDRESS)</label>
                                <input type="email" wire:model="mail_from_address" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom Expéditeur (MAIL_FROM_NAME)</label>
                                <input type="text" wire:model="mail_from_name" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: AWS CONFIG -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="cloud" class="w-5 h-5 text-indigo-500"></i> Stockage Cloud AWS S3 (AWS_)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AWS Access Key ID</label>
                                <input type="text" wire:model="aws_access_key_id" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AWS Secret Access Key</label>
                                <input type="password" wire:model="aws_secret_access_key" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">AWS Région</label>
                                <input type="text" wire:model="aws_default_region" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nom du Bucket S3</label>
                                <input type="text" wire:model="aws_bucket" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: SERVER & DIVERSE -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="sliders" class="w-5 h-5 text-indigo-500"></i> Paramètres Système, Avatar & Langue
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Langue par défaut</label>
                                <select wire:model="system_lang" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="fr">Français</option>
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stockage des Avatars</label>
                                <select wire:model="avatar_storage" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="local">Disque Public Local</option>
                                    <option value="s3">Stockage Cloud S3 (AWS)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Validation des Factures / Devis</label>
                                <select wire:model="devis_validation" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="manual">Validation Manuelle</option>
                                    <option value="auto">Validation Automatique</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Préfixe de Devis / Reçus</label>
                                <input type="text" wire:model="devis_prefix" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Taux Standard TVA (%)</label>
                                <input type="number" step="0.1" wire:model="devis_tax" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: INTÉGRATION ELECTRON & SERVEUR LOCAL -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="monitor-smartphone" class="w-5 h-5 text-indigo-500"></i> Intégration Electron & Client Desktop (Local Server)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Hôte Local (ELECTRON_HOST)</label>
                                <input type="text" wire:model="electron_host" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port de connexion (ELECTRON_PORT)</label>
                                <input type="text" wire:model="electron_port" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Intervalle de Sync Offline (Minutes)</label>
                                <select wire:model="offline_sync_interval" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                                    <option value="1">1 Minute</option>
                                    <option value="5">5 Minutes</option>
                                    <option value="15">15 Minutes</option>
                                    <option value="30">30 Minutes</option>
                                    <option value="manual">Manuel uniquement</option>
                                </select>
                            </div>
                            <div class="md:col-span-3 flex items-center gap-3">
                                <input type="checkbox" wire:model="electron_debug" id="electronDebugCheck" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                <label for="electronDebugCheck" class="text-sm font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Activer le débogage de l'application Desktop Electron (ELECTRON_DEBUG)</label>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: PARAMÈTRES API (PREFIX, RATE LIMITS, CORS) -->
                    <div class="p-6 border border-slate-200 dark:border-[#333] rounded-2xl bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-4 border-b border-slate-100 dark:border-[#1a1a1a] pb-3">
                            <i data-lucide="api" class="w-5 h-5 text-indigo-500"></i> Configurations API & CORS
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Préfixe de route (API_PREFIX)</label>
                                <input type="text" wire:model="api_prefix" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Limites de requêtes (API_RATE_LIMIT)</label>
                                <input type="number" wire:model="api_rate_limit" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Origine autorisée CORS (API_CORS_ORIGIN)</label>
                                <input type="text" wire:model="api_cors_origin" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Durée de vie Token (Minutes)</label>
                                <input type="number" wire:model="api_token_ttl" class="block w-full py-2 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Enregistrer les Paramètres Système, Serveurs & Env
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- CURRENCY TAB -->
            @if($activeTab === 'currency')
            <div class="animate-fade-in max-w-5xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Devises & Taux de Change</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gérez les monnaies acceptées, définissez la monnaie principale et configurez les taux d'échange.</p>
                </div>

                @if (session()->has('success_currencies'))
                    <div class="p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-xl text-sm flex items-center gap-3 mb-6">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        {{ session('success_currencies') }}
                    </div>
                @endif
                @if (session()->has('error_currencies'))
                    <div class="p-4 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30 rounded-xl text-sm flex items-center gap-3 mb-6">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        {{ session('error_currencies') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Add/Edit Currency Form -->
                    <div class="lg:col-span-1">
                        @if($editing_currency_id)
                            <form wire:submit.prevent="updateCurrency" class="p-5 border border-amber-100 dark:border-amber-900/30 bg-amber-50/20 dark:bg-amber-900/5 rounded-2xl space-y-4 animate-fade-in">
                                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-400 flex items-center gap-2 mb-2">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Modifier la Devise
                                </h3>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Code Devise (ex: USD)</label>
                                    <input type="text" wire:model="editing_currency_code" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Désignation (ex: Dollar Américain)</label>
                                    <input type="text" wire:model="editing_currency_name" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Symbole (ex: $)</label>
                                    <input type="text" wire:model="editing_currency_symbol" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Taux d'échange (relatif au principal)</label>
                                    <input type="number" step="0.0001" wire:model="editing_currency_exchange_rate" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model="editing_currency_is_default" id="editCurrDefault" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 cursor-pointer">
                                    <label for="editCurrDefault" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Définir comme devise par défaut</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model="editing_currency_is_active" id="editCurrActive" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 cursor-pointer">
                                    <label for="editCurrActive" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Devise active / acceptée</label>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button type="submit" class="flex-1 py-2 bg-amber-600 text-white text-xs font-semibold rounded-xl hover:bg-amber-700 transition-all">
                                        Enregistrer
                                    </button>
                                    <button type="button" wire:click="cancelEditCurrency" class="flex-1 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 transition-all">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        @else
                            <form wire:submit.prevent="createCurrency" class="p-5 border border-slate-200 dark:border-[#333] rounded-2xl space-y-4 bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-2">
                                    <i data-lucide="plus" class="w-4 h-4 text-indigo-500"></i> Nouvelle Devise
                                </h3>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Code Devise (ex: USD)</label>
                                    <input type="text" wire:model="new_currency_code" placeholder="ex: USD" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Désignation (ex: Dollar)</label>
                                    <input type="text" wire:model="new_currency_name" placeholder="ex: Dollar Américain" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Symbole (ex: $)</label>
                                    <input type="text" wire:model="new_currency_symbol" placeholder="ex: $" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Taux d'échange (relatif)</label>
                                    <input type="number" step="0.0001" wire:model="new_currency_exchange_rate" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model="new_currency_is_default" id="newCurrDefault" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                    <label for="newCurrDefault" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Définir comme devise par défaut</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model="new_currency_is_active" id="newCurrActive" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer">
                                    <label for="newCurrActive" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer select-none">Devise active / acceptée</label>
                                </div>
                                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Ajouter la Devise
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Right: Currencies Table -->
                    <div class="lg:col-span-2">
                        <div class="border border-slate-200 dark:border-[#333] rounded-xl overflow-hidden bg-white dark:bg-[#0a0a0a]">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Devise</th>
                                        <th class="px-4 py-3 font-semibold">Taux d'échange</th>
                                        <th class="px-4 py-3 font-semibold text-center">Par Défaut</th>
                                        <th class="px-4 py-3 font-semibold text-center">Status</th>
                                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                                    @forelse($currencies as $curr)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                                        {{ $curr['symbol'] ?: $curr['code'] }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white">{{ $curr['code'] }}</div>
                                                        <div class="text-xs text-slate-500">{{ $curr['name'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">
                                                {{ strpos($curr['exchange_rate'], '.') !== false ? rtrim(rtrim(number_format($curr['exchange_rate'], 4, '.', ','), '0'), '.') : number_format($curr['exchange_rate'], 0, '.', ',') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($curr['is_default'])
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30">
                                                        Principale
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($curr['is_active'])
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" wire:click="editCurrency({{ $curr['id'] }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 text-xs font-semibold rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-all">
                                                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Modifier
                                                    </button>
                                                    @if(!$curr['is_default'])
                                                        <button type="button" wire:click="deleteCurrency({{ $curr['id'] }})" wire:confirm="Confirmez-vous la suppression de cette devise ?" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 text-xs font-semibold rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-all">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Supprimer
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">Aucune devise configurée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- CAMPUSES TAB -->
            @if($activeTab === 'campuses')
            <div class="animate-fade-in max-w-5xl">
                <div class="mb-8 border-b border-slate-100 dark:border-[#1a1a1a] pb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Succursales & Campus</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gérez les différents sites ou succursales de votre établissement.</p>
                </div>

                @if (session()->has('success_campuses'))
                    <div class="p-4 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-xl text-sm flex items-center gap-3 mb-6">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        {{ session('success_campuses') }}
                    </div>
                @endif
                @if (session()->has('error_campuses'))
                    <div class="p-4 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30 rounded-xl text-sm flex items-center gap-3 mb-6">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        {{ session('error_campuses') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Add/Edit Campus Form -->
                    <div class="lg:col-span-1">
                        @if($editing_campus_id)
                            <form wire:submit.prevent="updateCampus" class="p-5 border border-amber-100 dark:border-amber-900/30 bg-amber-50/20 dark:bg-amber-900/5 rounded-2xl space-y-4 animate-fade-in">
                                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-400 flex items-center gap-2 mb-2">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Modifier la Succursale
                                </h3>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom du Campus / Succursale</label>
                                    <input type="text" wire:model="editing_campus_name" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Adresse physique / Localisation</label>
                                    <textarea wire:model="editing_campus_address" rows="3" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-amber-500 dark:text-white"></textarea>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button type="submit" class="flex-1 py-2 bg-amber-600 text-white text-xs font-semibold rounded-xl hover:bg-amber-700 transition-all">
                                        Enregistrer
                                    </button>
                                    <button type="button" wire:click="cancelEditCampus" class="flex-1 py-2 bg-white dark:bg-[#111] text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl border border-slate-200 dark:border-[#333] hover:bg-slate-50 transition-all">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        @else
                            <form wire:submit.prevent="addCampus" class="p-5 border border-slate-200 dark:border-[#333] rounded-2xl space-y-4 bg-slate-50/50 dark:bg-[#0a0a0a]/50">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-2">
                                    <i data-lucide="plus" class="w-4 h-4 text-indigo-500"></i> Nouvelle Succursale
                                </h3>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nom du Campus / Succursale</label>
                                    <input type="text" wire:model="new_campus_name" placeholder="ex: Campus Principal / Annexe Gombe" class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Adresse physique / Localisation</label>
                                    <textarea wire:model="new_campus_address" rows="3" placeholder="Adresse complète..." class="block w-full py-1.5 px-3 text-sm border border-slate-200 dark:border-[#333] rounded-lg bg-white dark:bg-[#0a0a0a] focus:ring-2 focus:ring-indigo-500 dark:text-white"></textarea>
                                </div>
                                <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Ajouter la Succursale
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Right: Campuses Table -->
                    <div class="lg:col-span-2">
                        <div class="border border-slate-200 dark:border-[#333] rounded-xl overflow-hidden bg-white dark:bg-[#0a0a0a]">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-[#111] border-b border-slate-200 dark:border-[#333]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Nom de la Succursale</th>
                                        <th class="px-4 py-3 font-semibold">Adresse / Localisation</th>
                                        <th class="px-4 py-3 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                                    @forelse($campuses as $camp)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-[#111]/30 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white">{{ $camp['name'] }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                {{ $camp['address'] ?: 'Adresse non renseignée' }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" wire:click="editCampus({{ $camp['id'] }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 text-xs font-semibold rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-all">
                                                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i> Modifier
                                                    </button>
                                                    <button type="button" wire:click="deleteCampus({{ $camp['id'] }})" wire:confirm="Confirmez-vous la suppression de cette succursale ?" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 text-xs font-semibold rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/30 transition-all">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Supprimer
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-slate-400">Aucune succursale configurée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
