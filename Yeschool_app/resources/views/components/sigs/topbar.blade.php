<!-- Topbar -->
<header class="sticky top-0 z-30 w-full bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-[#1a1a1a] px-4 py-3 flex items-center justify-between gap-4 transition-colors duration-300">
    
    <!-- Left Section: Sidebar Toggle + Breadcrumb -->
    <div class="flex items-center gap-3">
        <!-- Sidebar Toggle -->
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] rounded-lg transition-all duration-200"
            :title="sidebarOpen ? 'Réduire le menu' : 'Étendre le menu'"
        >
            <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="!sidebarOpen" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16" />
            </svg>
        </button>

        <div class="h-5 w-px bg-slate-200 dark:bg-[#333]"></div>

        <!-- Breadcrumb -->
        <div class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="font-medium text-slate-800 dark:text-slate-200">Tableau de bord</span>
        </div>
    </div>

    <!-- Center Section: Global Search -->
    <div class="flex-1 max-w-md hidden md:block">
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-slate-600 dark:group-focus-within:text-slate-300 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <input 
                type="text" 
                class="block w-full py-2 pl-10 pr-16 text-sm text-slate-900 border border-slate-200 rounded-lg bg-slate-50 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 focus:bg-white dark:bg-[#111] dark:border-[#333] dark:placeholder-slate-500 dark:text-white dark:focus:bg-[#0a0a0a] dark:focus:ring-slate-600 dark:focus:border-slate-600 transition-all placeholder:text-slate-400" 
                placeholder="Rechercher... (⌘K)" 
            >
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <kbd class="hidden sm:inline-flex items-center border border-slate-200 dark:border-[#333] rounded-md px-2 py-0.5 text-xs text-slate-400 dark:text-slate-500 font-sans bg-white dark:bg-[#1a1a1a] gap-0.5">⌘K</kbd>
            </div>
        </div>
    </div>

    <!-- Right Section: Actions & Profile -->
    <div class="flex items-center gap-2">
        
        <!-- Year Selector -->
        @php
            $topbarYears = \App\Models\AcademicYear::all();
            $currentSelectedYearId = session('selected_academic_year_id');
            if (!$currentSelectedYearId && count($topbarYears) > 0) {
                $currentSelectedYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id ?? $topbarYears->first()->id;
                session(['selected_academic_year_id' => $currentSelectedYearId]);
            }
        @endphp
        <select 
            onchange="window.location.href='/admin/select-year/' + this.value"
            class="hidden lg:block border border-slate-200 dark:border-[#333] bg-white dark:bg-[#111] rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 cursor-pointer hover:border-slate-400 dark:hover:border-slate-500 transition-colors"
        >
            @foreach($topbarYears as $year)
                <option value="{{ $year->id }}" {{ $currentSelectedYearId == $year->id ? 'selected' : '' }}>
                    {{ $year->name }}
                </option>
            @endforeach
        </select>

        <!-- ======================== -->
        <!-- THEME TOGGLE: Vercel Pro -->
        <!-- ======================== -->
        <div class="relative flex items-center h-8 p-1 bg-slate-100 dark:bg-[#1a1a1a] border border-slate-200 dark:border-[#333] rounded-[10px]">
            <!-- Sliding Indicator -->
            <div class="theme-indicator absolute top-1 left-1 h-6 w-[30px] rounded-[7px] bg-white dark:bg-[#333] shadow-sm border border-slate-200/50 dark:border-[#444]/50"
                 :style="`transform: translateX(${indicatorOffset}px)`"></div>
            
            <!-- System Button -->
            <button 
                @click="setTheme('system')" 
                class="relative z-10 flex items-center justify-center w-[30px] h-6 rounded-[7px] transition-colors duration-200"
                :class="theme === 'system' ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300'"
                title="Thème système"
            >
                <!-- Monitor SVG -->
                <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </button>

            <!-- Light Button -->
            <button 
                @click="setTheme('light')" 
                class="relative z-10 flex items-center justify-center w-[30px] h-6 rounded-[7px] transition-colors duration-200"
                :class="theme === 'light' ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300'"
                title="Mode clair"
            >
                <!-- Sun SVG -->
                <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>

            <!-- Dark Button -->
            <button 
                @click="setTheme('dark')" 
                class="relative z-10 flex items-center justify-center w-[30px] h-6 rounded-[7px] transition-colors duration-200"
                :class="theme === 'dark' ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300'"
                title="Mode sombre"
            >
                <!-- Moon SVG -->
                <svg class="w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
        </div>
        <!-- ======================== -->

        @php
            $topbarToday = \Carbon\Carbon::today();
            $topbarTodayMD = $topbarToday->format('m-d');
            
            $driverName = \DB::connection()->getDriverName();
            $sqlFormat = $driverName === 'sqlite' 
                ? "strftime('%m-%d', date_of_birth) = ?" 
                : "DATE_FORMAT(date_of_birth, '%m-%d') = ?";
            
            $todayStudentCount = \App\Models\Student::whereRaw($sqlFormat, [$topbarTodayMD])->count();
            $todayTeacherCount = \App\Models\Teacher::where('status', 'Actif')
                ->whereRaw($sqlFormat, [$topbarTodayMD])
                ->count();
                
            $upcomingBirthdaysCount = 0;
            for ($i = 1; $i <= 7; $i++) {
                $nextMD = $topbarToday->copy()->addDays($i)->format('m-d');
                $upcomingBirthdaysCount += \App\Models\Student::whereRaw($sqlFormat, [$nextMD])->count();
                $upcomingBirthdaysCount += \App\Models\Teacher::where('status', 'Actif')
                    ->whereRaw($sqlFormat, [$nextMD])
                    ->count();
            }
            
            $unreadAdmissionsCount = \App\Models\Admission::where('is_read', false)->count();
            $totalNotificationsCount = $todayStudentCount + $todayTeacherCount + $upcomingBirthdaysCount + $unreadAdmissionsCount;
        @endphp

        <!-- Notifications Toggle -->
        <button 
            @click="notificationsOpen = true" 
            class="relative flex items-center justify-center w-9 h-9 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1a1a1a] rounded-lg transition-all duration-200 border border-transparent hover:border-slate-200 dark:hover:border-[#333]"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            @if($totalNotificationsCount > 0)
                <span class="absolute -top-1 -right-1 min-w-4 h-4 px-1 bg-rose-600 text-[9px] font-black text-white rounded-full flex items-center justify-center border border-white dark:border-[#0a0a0a] shadow-sm">
                    {{ $totalNotificationsCount }}
                </span>
            @endif
        </button>

        <div class="h-6 w-px bg-slate-200 dark:bg-[#333] mx-0.5"></div>

        <!-- User Dropdown -->
        <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" class="flex items-center gap-2.5 py-1.5 px-2.5 hover:bg-slate-100 dark:hover:bg-[#1a1a1a] border border-transparent hover:border-slate-200 dark:hover:border-[#333] rounded-lg cursor-pointer transition-all duration-200 group">
                <img alt="{{ auth()->user()->name }}" src="{{ auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=4f46e5&color=fff' }}" class="w-7 h-7 rounded-full ring-2 ring-slate-200 dark:ring-[#333] group-hover:ring-slate-400 dark:group-hover:ring-slate-600 transition-all object-cover" />
                <div class="hidden lg:block">
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 leading-none mt-0.5">{{ auth()->user()->email }}</p>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors hidden lg:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
            <ul tabindex="0" class="mt-2 z-[50] p-1.5 shadow-xl menu dropdown-content bg-white dark:bg-[#111] border border-slate-200 dark:border-[#333] rounded-xl w-56">
                <li class="px-3 py-2 mb-1">
                    <div class="flex items-center gap-3">
                        <img src="{{ auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=4f46e5&color=fff' }}" class="w-8 h-8 rounded-full object-cover">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white leading-none">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </li>
                <div class="h-px bg-slate-100 dark:bg-[#222] my-1 mx-2"></div>
                <li>
                    <a href="{{ \App\Filament\Pages\ProProfile::getUrl() }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#1a1a1a] rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Mon profil
                    </a>
                </li>
                <li>
                    <a class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-[#1a1a1a] rounded-lg transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Paramètres
                    </a>
                </li>
                <div class="h-px bg-slate-100 dark:bg-[#222] my-1 mx-2"></div>
                <li>
                    <form id="logout-form" method="POST" action="/admin/logout" class="hidden">
                        @csrf
                    </form>
                    <button type="button" onclick="window.confirmAction({
                        title: 'Se déconnecter ?',
                        message: 'Êtes-vous sûr de vouloir vous déconnecter de votre session ?',
                        confirmText: 'Se déconnecter',
                        cancelText: 'Annuler',
                        type: 'danger',
                        onConfirm: () => document.getElementById('logout-form').submit()
                    })" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        Déconnexion
                    </button>
                </li>
            </ul>
        </div>

    </div>
</header>
