@php
    $today = \Carbon\Carbon::today();
    $todayMonthDay = $today->format('m-d');
    
    $driverName = \DB::connection()->getDriverName();
    $sqlFormat = $driverName === 'sqlite' 
        ? "strftime('%m-%d', date_of_birth) = ?" 
        : "DATE_FORMAT(date_of_birth, '%m-%d') = ?";
    
    // Today's birthdays - Students
    $todayStudentBirthdays = \App\Models\Student::whereRaw($sqlFormat, [$todayMonthDay])->get();
    
    // Today's birthdays - Staff
    $todayTeacherBirthdays = \App\Models\Teacher::where('status', 'Actif')
        ->whereRaw($sqlFormat, [$todayMonthDay])
        ->get();
        
    // Upcoming birthdays (next 7 days, excluding today)
    $upcomingBirthdays = [];
    
    for ($i = 1; $i <= 7; $i++) {
        $nextDay = $today->copy()->addDays($i);
        $nextMonthDay = $nextDay->format('m-d');
        
        $st = \App\Models\Student::whereRaw($sqlFormat, [$nextMonthDay])->get();
        foreach ($st as $s) {
            $upcomingBirthdays[] = [
                'name' => "{$s->first_name} {$s->last_name}",
                'type' => 'Élève',
                'date_of_birth' => $s->date_of_birth,
                'days_left' => $i,
                'date_formatted' => $nextDay->translatedFormat('d F'),
                'phone' => $s->phone
            ];
        }
        
        $tc = \App\Models\Teacher::where('status', 'Actif')
            ->whereRaw($sqlFormat, [$nextMonthDay])->get();
        foreach ($tc as $t) {
            $upcomingBirthdays[] = [
                'name' => "{$t->first_name} {$t->last_name}",
                'type' => 'Collaborateur RH',
                'date_of_birth' => $t->date_of_birth,
                'days_left' => $i,
                'date_formatted' => $nextDay->translatedFormat('d F'),
                'phone' => $t->phone
            ];
        }
    }
    
    $totalBirthdaysCount = $todayStudentBirthdays->count() + $todayTeacherBirthdays->count() + count($upcomingBirthdays);
    $totalNotificationsCount = 3 + $totalBirthdaysCount;
@endphp

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
                                            @if($student->phone)
                                                <div class="mt-3">
                                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $student->phone) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
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

                        <!-- STANDARD ALERTS -->
                        <div class="space-y-3">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider px-1">Alertes Système</div>
                            
                            <!-- Notification Item 1 -->
                            <div class="flex gap-4 p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900/30">
                                <div class="mt-1 flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Nouvelle demande d'admission</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Le dossier de <strong>Jean Dupont</strong> (6ème) est en attente de validation.</p>
                                    <div class="mt-3 flex gap-2">
                                        <button class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Valider</button>
                                        <button class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Détails</button>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-2">Il y a 10 min</p>
                                </div>
                            </div>

                            <!-- Notification Item 2 -->
                            <div class="flex gap-4 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-[#111]/50 transition-colors">
                                <div class="mt-1 flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Paiement reçu</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Scolarité T1 réglée pour <strong>Sophie Martin</strong>. Montant : 450€</p>
                                    <p class="text-xs text-slate-400 mt-2">Il y a 1 heure</p>
                                </div>
                            </div>

                            <!-- Notification Item 3 -->
                            <div class="flex gap-4 p-4 rounded-xl hover:bg-slate-50 dark:hover:bg-[#111]/50 transition-colors">
                                <div class="mt-1 flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Absence signalée</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Professeur <strong>M. Lemaire</strong> absent ce jour. 3 cours nécessitent un remplacement.</p>
                                    <p class="text-xs text-slate-400 mt-2">Il y a 2 heures</p>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- End Notifications List -->

                    <div class="mt-8 text-center">
                        <button class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                            Marquer tout comme lu
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
