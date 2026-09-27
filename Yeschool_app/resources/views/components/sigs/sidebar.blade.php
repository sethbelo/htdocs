<!-- Sidebar -->
<aside 
    class="flex flex-col h-screen overflow-y-auto bg-white dark:bg-[#0a0a0a] border-r border-slate-200 dark:border-[#1a1a1a] transition-all duration-[280ms] ease-in-out will-change-[width]"
    :class="{ 'w-[68px] px-3 py-6': !sidebarOpen, 'w-64 px-4 py-8': sidebarOpen }"
>
    <!-- Logo -->
    <div class="flex items-center mb-8" :class="sidebarOpen ? 'gap-3 px-2' : 'justify-center'">
        <div class="w-8 h-8 flex-shrink-0 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
        </div>
        <h2 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity duration-200" 
            x-transition:enter-start="opacity-0" 
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="text-xl font-bold font-space bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 whitespace-nowrap"
        >
            Yebook
        </h2>
    </div>

    <!-- Navigation -->
    <nav class="flex flex-col gap-1 flex-1">
        
        <!-- Dashboard Link -->
        <a href="{{ \App\Filament\Pages\ProDashboard::getUrl() }}" class="sidebar-link {{ request()->url() === \App\Filament\Pages\ProDashboard::getUrl() ? 'active text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} flex items-center gap-3 px-3 py-2.5 mb-6 group">
            <i data-lucide="layout-dashboard" class="w-5 h-5 {{ request()->url() === \App\Filament\Pages\ProDashboard::getUrl() ? '' : 'group-hover:scale-110 transition-transform' }}"></i>
            <span x-show="sidebarOpen" class="font-medium text-sm">Dashboard</span>
        </a>

        <!-- Messagerie Link -->
        <a href="{{ \App\Filament\Pages\ProChat::getUrl() }}" class="sidebar-link {{ request()->url() === \App\Filament\Pages\ProChat::getUrl() ? 'active text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} flex items-center gap-3 px-3 py-2.5 mb-6 group">
            <i data-lucide="message-square" class="w-5 h-5 {{ request()->url() === \App\Filament\Pages\ProChat::getUrl() ? '' : 'group-hover:scale-110 transition-transform' }}"></i>
            <span x-show="sidebarOpen" class="font-medium text-sm">Messagerie</span>
        </a>

        <!-- Section: Scolarité & Pédagogie -->
        @if(auth()->user()->hasAnyRole(['Super Admin', 'Directeur General', 'Directeur Général', 'Prefet', 'Enseignant', 'Titulaire', 'Surveillant', 'Secretaire', 'Secrétaire']))
            <div x-show="sidebarOpen" class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-2 px-3">Scolarité</div>
            
            @if(!auth()->user()->hasRole('Enseignant'))
            <a href="{{ \App\Filament\Pages\ProAdmissions::getUrl() }}" class="sidebar-link {{ request()->url() === \App\Filament\Pages\ProAdmissions::getUrl() ? 'active text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} flex items-center justify-between px-3 py-2 group">
                <div class="flex items-center gap-3">
                    <i data-lucide="user-plus" class="w-5 h-5 {{ request()->url() === \App\Filament\Pages\ProAdmissions::getUrl() ? '' : 'group-hover:scale-110 transition-transform' }}"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Admissions</span>
                </div>
                <span x-show="sidebarOpen" class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 text-xs font-bold px-2 py-0.5 rounded-full">+12</span>
            </a>
            @endif

            @if(!auth()->user()->hasRole('Enseignant'))
            <a href="{{ \App\Filament\Pages\ProEnrollments::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.inscriptions') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="user-plus" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.inscriptions') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Inscriptions</span>
            </a>
            @endif

            <a href="{{ \App\Filament\Pages\ProStudents::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-students') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="users" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-students') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Élèves</span>
            </a>

            <a href="{{ \App\Filament\Pages\ProClasses::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-classes') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="layers" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-classes') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Classes & Niveaux</span>
            </a>

            @if(!auth()->user()->hasRole('Enseignant'))
            <a href="{{ \App\Filament\Pages\ProAcademicYears::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-academic-years') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="calendar" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-academic-years') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Années Scolaires</span>
            </a>
            @endif

            <div x-show="sidebarOpen" class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6 px-3">Pédagogie</div>
            
            <a href="{{ \App\Filament\Pages\ProTimetable::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-timetable') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="calendar-days" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-timetable') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Emploi du temps</span>
            </a>

            <a href="{{ \App\Filament\Pages\ProGrades::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-grades') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="file-text" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-grades') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Notes & Bulletins</span>
            </a>

            <a href="{{ \App\Filament\Pages\ProAttendances::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-attendances') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="check-square" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-attendances') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Présences & WhatsApp</span>
            </a>

            <!-- Journal de classe -->
            <a href="{{ \App\Filament\Pages\ProClassJournals::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-class-journals') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="book-open" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-class-journals') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Journal de classe</span>
            </a>

            <!-- Notes de cours -->
            <a href="{{ \App\Filament\Pages\ProCourseNotes::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-course-notes') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="file-signature" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-course-notes') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Notes de cours</span>
            </a>

            @if(!auth()->user()->hasRole('Enseignant'))
            <a href="{{ \App\Filament\Pages\ProSubjects::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-subjects') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="book-open" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-subjects') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Matières & Cours</span>
            </a>
            @endif
        @endif

        <!-- Section: Administration -->
        @if(auth()->user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Comptable', 'Caissier', 'Directeur General', 'Directeur Général']))
            <div x-show="sidebarOpen" class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 mt-6 px-3">Administration</div>
            
            <!-- HR -->
            @if(auth()->user()->hasAnyRole(['Super Admin', 'Directeur General', 'Directeur Général']) && !auth()->user()->hasAnyRole(['RH', 'Responsable RH']))
                <a href="{{ \App\Filament\Pages\ProHR::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r') && !request()->query('tab') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="users" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r') && !request()->query('tab') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Personnel & RH</span>
                </a>
            @endif

            @if(auth()->user()->hasAnyRole(['RH', 'Responsable RH']))
                <!-- Annuaire & Recrutement -->
                <a href="{{ \App\Filament\Pages\ProHRDirectory::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-directory') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="users" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-directory') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Employer</span>
                </a>

                <!-- Départements -->
                <a href="{{ \App\Filament\Pages\ProHRDepartments::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-departments') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="folder-open" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-departments') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Départements</span>
                </a>

                <!-- Gestion des Congés -->
                <a href="{{ \App\Filament\Pages\ProHRLeaves::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-leaves') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="calendar" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-leaves') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Congés</span>
                </a>

                <!-- Gestion des Absences -->
                <a href="{{ \App\Filament\Pages\ProHRAbsences::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-absences') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="clock" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-absences') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Prestation</span>
                </a>

                <!-- Rémunération & Bulletins Paie -->
                <a href="{{ \App\Filament\Pages\ProHRPayroll::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-payroll') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="credit-card" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-payroll') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Rémunération</span>
                </a>

                <!-- Gestion des Conflits -->
                <a href="{{ \App\Filament\Pages\ProHRConflicts::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-conflicts') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="shield-alert" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-conflicts') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Conflits d'équipe</span>
                </a>

                <!-- Reporting & Analyses -->
                <a href="{{ \App\Filament\Pages\ProHRAnalytics::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-h-r-analytics') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="presentation" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-h-r-analytics') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Reporting & Analyses</span>
                </a>
            @endif

            <!-- Finance -->
            @if(auth()->user()->hasAnyRole(['Super Admin', 'Comptable', 'Caissier', 'Directeur General', 'Directeur Général']))
                <a href="{{ \App\Filament\Pages\ProAccounting::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-accounting') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="credit-card" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-accounting') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Finances</span>
                </a>

                <a href="{{ \App\Filament\Pages\ProFeeManagement::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.frais-scolaires') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                    <i data-lucide="dollar-sign" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.frais-scolaires') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                    <span x-show="sidebarOpen" class="font-medium text-sm">Frais Scolaires</span>
                </a>
            @endif
        @endif

        <!-- Configuration Settings (Super Admin only) -->
        @if(auth()->user()->hasAnyRole(['Super Admin']))
            <a href="{{ \App\Filament\Pages\ProSettings::getUrl() }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-[#1a1a1a] transition-all group {{ request()->routeIs('filament.admin.pages.pro-settings') ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : '' }}">
                <i data-lucide="settings" class="w-5 h-5 {{ request()->routeIs('filament.admin.pages.pro-settings') ? 'text-indigo-600 dark:text-indigo-400' : 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400' }} transition-colors"></i>
                <span x-show="sidebarOpen" class="font-medium text-sm">Configuration</span>
            </a>
        @endif

    </nav>

    <!-- Bottom User Quick Profile -->
    <a href="{{ \App\Filament\Pages\ProProfile::getUrl() }}" class="mt-auto pt-6 border-t border-slate-200 dark:border-[#1a1a1a] hover:bg-slate-100 dark:hover:bg-[#1a1a1a] rounded-xl p-2 transition-colors block" x-show="sidebarOpen">
        <div class="flex items-center gap-3">
            <img src="{{ auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=4f46e5&color=fff' }}" alt="{{ auth()->user()->name }}" class="w-10 h-10 rounded-full border-2 border-slate-100 dark:border-[#333] shadow-sm object-cover">
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate max-w-[140px]">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-500 truncate max-w-[140px]">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </a>
</aside>
