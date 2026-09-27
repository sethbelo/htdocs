@php
    $totalNotificationsCount = $todayStudentBirthdays->count() + $todayTeacherBirthdays->count() + count($upcomingBirthdays) + $unreadAdmissions->count();
@endphp

<div>
    <!-- Notifications Offcanvas Panel -->
    <div 
        x-show="notificationsOpen" 
        class="fixed inset-0 z-50 overflow-hidden" 
        aria-labelledby="slide-over-title" 
        role="dialog" 
        aria-modal="true"
        x-cloak
    >
        <!-- Background overlay -->
        <div 
            x-show="notificationsOpen"
            x-transition:enter="ease-in-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
            @click="notificationsOpen = false"
        ></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <!-- Slide-over panel -->
            <div 
                x-show="notificationsOpen"
                x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="pointer-events-auto w-screen max-w-md"
            >
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-[#0a0a0a] shadow-2xl border-l border-slate-200 dark:border-[#333]">
                    <div class="px-6 py-6 sm:px-8 border-b border-slate-200 dark:border-[#333] bg-slate-50/50 dark:bg-[#111]/50">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white" id="slide-over-title">Notifications</h2>
                                <p class="text-sm text-slate-500 mt-1">Vous avez {{ $totalNotificationsCount }} alertes actives</p>
                            </div>
                            <div class="ml-3 flex h-7 items-center">
                                <button @click="notificationsOpen = false" type="button" class="rounded-full p-2 bg-white dark:bg-slate-800 text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 border border-slate-200 dark:border-slate-700 hover:scale-105 transition-transform shadow-sm">
                                    <span class="sr-only">Fermer le panneau</span>
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Alert within Panel -->
                    @if (session()->has('success_notification'))
                        <div class="m-4 p-3 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 rounded-xl text-xs flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            {{ session('success_notification') }}
                        </div>
                    @endif
                    
                    <div class="relative flex-1 px-4 py-6 sm:px-6">
                        <!-- Notifications List -->
                        <div class="space-y-6">
                            
                            <!-- 🎂 SECTION: TODAY'S BIRTHDAYS -->
                            @if($todayStudentBirthdays->isNotEmpty() || $todayTeacherBirthdays->isNotEmpty())
                                <div class="space-y-3">
                                    <div class="text-xs font-bold text-rose-500 uppercase tracking-wider px-1">🎂 Anniversaires d'aujourd'hui</div>
                                    
                                    @foreach($todayStudentBirthdays as $student)
                                        <div class="flex gap-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/15 border border-rose-100 dark:border-rose-900/30">
                                            <div class="mt-1 flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400 font-bold text-lg">
                                                    🎂
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Anniversaire Élève !</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                    C'est l'anniversaire de l'élève <strong>{{ $student->first_name }} {{ $student->last_name }}</strong> aujourd'hui !
                                                </p>
                                                @if($student->parent_phone)
                                                    <div class="mt-3">
                                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $student->parent_phone) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Souhaiter par WhatsApp
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    @foreach($todayTeacherBirthdays as $teacher)
                                        <div class="flex gap-4 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/15 border border-rose-100 dark:border-rose-900/30">
                                            <div class="mt-1 flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400 font-bold text-lg">
                                                    🎂
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Anniversaire Collaborateur !</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                    C'est l'anniversaire de <strong>{{ $teacher->first_name }} {{ $teacher->last_name }}</strong> (Personnel RH) aujourd'hui !
                                                </p>
                                                @if($teacher->phone)
                                                    <div class="mt-3">
                                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $teacher->phone) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                                                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Souhaiter par WhatsApp
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- 🎉 SECTION: UPCOMING BIRTHDAYS -->
                            @if(!empty($upcomingBirthdays))
                                <div class="space-y-3">
                                    <div class="text-xs font-bold text-indigo-500 uppercase tracking-wider px-1">🎉 Anniversaires à venir (7 jours)</div>
                                    
                                    @foreach($upcomingBirthdays as $item)
                                        <div class="flex gap-4 p-4 rounded-xl bg-slate-50/50 dark:bg-[#111]/30 border border-slate-200 dark:border-slate-800">
                                            <div class="mt-1 flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/20 flex items-center justify-center text-indigo-500 dark:text-indigo-400">
                                                    🎁
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['type'] }}</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                    <strong>{{ $item['name'] }}</strong> fêtera son anniversaire le <strong>{{ $item['date_formatted'] }}</strong> (dans {{ $item['days_left'] }} {{ $item['days_left'] == 1 ? 'jour' : 'jours' }}).
                                                </p>
                                                @if($item['phone'])
                                                    <div class="mt-2.5">
                                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $item['phone']) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                                            <i data-lucide="message-circle" class="w-3 h-3"></i> WhatsApp
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- SYSTEM ALERTS (UNREAD) -->
                            <div class="space-y-3">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider px-1">Alertes Système (Non lues)</div>
                                
                                @forelse($unreadAdmissions as $adm)
                                    <div class="flex gap-4 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900/30">
                                        <div class="mt-1 flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Nouvelle demande d'admission</h4>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                                Le dossier de <strong>{{ $adm->first_name }} {{ $adm->last_name }}</strong> (Classe: {{ $adm->requested_class }}) est en attente de validation.
                                            </p>
                                            <div class="mt-3 flex gap-2 flex-wrap">
                                                @if($adm->status === 'En attente')
                                                    <button wire:click="validateAdmission({{ $adm->id }})" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-1">
                                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Valider
                                                    </button>
                                                @else
                                                    <span class="inline-flex px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 text-[10px] font-bold rounded-lg uppercase">
                                                        {{ $adm->status }}
                                                    </span>
                                                @endif
                                                <button wire:click="viewDetails({{ $adm->id }})" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-1">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Détails
                                                </button>
                                                <button wire:click="markAsRead({{ $adm->id }})" class="px-2.5 py-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 text-xs font-semibold flex items-center gap-1" title="Marquer comme lu">
                                                    <i data-lucide="check-square" class="w-3.5 h-3.5"></i> Lu
                                                </button>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-2.5 flex items-center gap-1">
                                                <i data-lucide="clock" class="w-3 h-3"></i>
                                                {{ \Carbon\Carbon::parse($adm->created_at)->locale('fr')->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-400 italic">
                                        Aucune nouvelle alerte d'admission non lue.
                                    </div>
                                @endforelse
                            </div>

                            <!-- SYSTEM ALERTS (READ / ARCHIVED) -->
                            @if($readAdmissions->isNotEmpty())
                                <div class="space-y-3 pt-2">
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider px-1">Alertes lues (Historique)</div>
                                    
                                    @foreach($readAdmissions as $adm)
                                        <div class="flex gap-4 p-4 rounded-xl border border-slate-100 dark:border-[#222] opacity-60 hover:opacity-100 transition-opacity">
                                            <div class="mt-1 flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-400">
                                                    <i data-lucide="user" class="w-5 h-5"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-300">Demande d'admission lue</h4>
                                                <p class="text-xs text-slate-500 mt-1">
                                                    Dossier de <strong>{{ $adm->first_name }} {{ $adm->last_name }}</strong>. Statut : <span class="font-bold text-slate-700 dark:text-slate-300">{{ $adm->status }}</span>.
                                                </p>
                                                <div class="mt-2.5 flex gap-2">
                                                    <button wire:click="viewDetails({{ $adm->id }})" class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[10px] font-bold rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                                        Détails
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-slate-400 mt-2">
                                                    {{ \Carbon\Carbon::parse($adm->created_at)->locale('fr')->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                        <!-- End Notifications List -->

                        @if($unreadAdmissions->isNotEmpty())
                            <div class="mt-8 text-center border-t border-slate-100 dark:border-[#1a1a1a] pt-4">
                                <button wire:click="markAllAsRead" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center justify-center gap-1.5 mx-auto">
                                    <i data-lucide="check-all" class="w-4 h-4"></i>
                                    Marquer tout comme lu
                                </button>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL MODAL FOR ADMISSIONS -->
    @if($isDetailsModalOpen && $selectedAdmission)
        <div class="fixed inset-0 z-[60] flex items-center justify-center" x-cloak>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm" wire:click="closeDetailsModal"></div>

            <!-- Modal Content Box -->
            <div class="relative w-full max-w-lg bg-white dark:bg-[#0a0a0a] border border-slate-200 dark:border-[#222] rounded-2xl shadow-2xl overflow-hidden animate-fade-in mx-4">
                
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-[#1a1a1a]">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-indigo-500"></i> Détails de l'admission
                    </h3>
                    <button wire:click="closeDetailsModal" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 dark:hover:bg-[#222] transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Body Scroll Area -->
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Status Banner -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border {{ $selectedAdmission->status === 'Accepté' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/10 dark:border-emerald-900/30 dark:text-emerald-400' : 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/10 dark:border-amber-900/30 dark:text-amber-400' }}">
                        <span class="text-xs font-semibold uppercase tracking-wider">Statut du dossier :</span>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white dark:bg-slate-950 border {{ $selectedAdmission->status === 'Accepté' ? 'border-emerald-300 text-emerald-700' : 'border-amber-300 text-amber-700' }}">
                            {{ $selectedAdmission->status }}
                        </span>
                    </div>

                    <!-- Personal Information Grid -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Informations Élève</h4>
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-[#111] p-4 rounded-xl border border-slate-100 dark:border-[#1c1c1c]">
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Nom complet</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $selectedAdmission->first_name }} {{ $selectedAdmission->last_name }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Classe demandée</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $selectedAdmission->requested_class }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Date de naissance</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $selectedAdmission->date_of_birth ? \Carbon\Carbon::parse($selectedAdmission->date_of_birth)->format('d/m/Y') : 'Non renseignée' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Genre / Sexe</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $selectedAdmission->gender === 'F' ? 'Féminin' : 'Masculin' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-[10px] text-slate-500 block uppercase">Adresse de résidence</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $selectedAdmission->address ?: 'Non spécifiée' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Parent / Guardian Info -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Informations Tuteur / Parent</h4>
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-[#111] p-4 rounded-xl border border-slate-100 dark:border-[#1c1c1c]">
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Nom du parent</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $selectedAdmission->parent_name ?: 'Non renseigné' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">WhatsApp / Tél</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $selectedAdmission->parent_phone ?: 'Non renseigné' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-[10px] text-slate-500 block uppercase">Courriel du parent</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $selectedAdmission->parent_email ?: 'Non renseigné' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Détails d'Évaluation & Santé</h4>
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-[#111] p-4 rounded-xl border border-slate-100 dark:border-[#1c1c1c]">
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">Note d'admission</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ $selectedAdmission->score }} / 100</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 block uppercase">École précédente</span>
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $selectedAdmission->previous_school ?: 'Non spécifiée' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-[10px] text-slate-500 block uppercase">Notes médicales ou remarques</span>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 italic">{{ $selectedAdmission->medical_notes ?: 'Aucune remarque médicale.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-[#0f0f0f]/30 border-t border-slate-100 dark:border-[#1a1a1a] flex gap-3">
                    <button type="button" wire:click="closeDetailsModal" class="flex-1 py-2.5 text-xs bg-slate-100 hover:bg-slate-200 dark:bg-[#1a1a1a] dark:text-slate-300 dark:hover:bg-[#222] font-semibold rounded-xl transition-all">
                        Fermer
                    </button>
                    @if($selectedAdmission->status === 'En attente')
                        <button type="button" wire:click="validateAdmission({{ $selectedAdmission->id }})" class="flex-1 py-2.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-md shadow-indigo-500/10 flex items-center justify-center gap-1">
                            <i data-lucide="check" class="w-4 h-4"></i> Valider & Inscrire l'Élève
                        </button>
                    @endif
                </div>

            </div>
        </div>
    @endif
</div>
