@php
    $user = auth()->user();
    $role = $user ? ($user->roles->first()?->name ?? 'Enseignant') : 'Enseignant';
    
    $isAdmin = in_array($role, ['Super Admin', 'Directeur General', 'Proprietaire', 'Prefet']);
    $isFinance = in_array($role, ['Comptable', 'Caissier']);
    $isTeacher = in_array($role, ['Enseignant', 'Titulaire']);
    $isHR = ($role === 'RH');
    $isSecretary = ($role === 'Secretaire');

    // Get last 6 months data for Admissions and Payments (Revenues)
    $months = [];
    $admissionsCounts = [];
    $revenuesAmounts = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $months[] = ucfirst($date->translatedFormat('M')); // e.g. "Févr.", "Mars", "Avr.", "Mai", "Juin", "Juil."
        
        $startDate = $date->copy()->startOfMonth()->toDateString();
        $endDate = $date->copy()->endOfMonth()->toDateString();
        
        $count = \App\Models\Admission::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
        $admissionsCounts[] = $count;
        
        $sum = \App\Models\Payment::where('transaction_type', 'Entrée')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        $revenuesAmounts[] = round($sum / 1000, 2);
    }

    // Get general students count
    $totalStudentsCount = \App\Models\Student::count();

    // Get dynamic global attendance rate
    $totalGlobalAttendance = \App\Models\Attendance::count();
    if ($totalGlobalAttendance > 0) {
        $globalPresentCount = \App\Models\Attendance::whereIn('status', ['present', 'late'])->count();
        $globalAttendanceRate = round(($globalPresentCount / $totalGlobalAttendance) * 100, 1) . '%';
    } else {
        $globalAttendanceRate = '95.4%'; // realistic fallback
    }

    // Get teacher specific counts if user has teacher role
    $teacher = null;
    $myClassesCount = 0;
    $myStudentsCount = 0;
    $homeworkToGradeCount = 0;
    $teacherAttendanceRate = '96.8%';
    
    if ($isTeacher) {
        // Find teacher associated with the logged-in user, fallback to teacher ID 3 (seeded with active courses) for testing
        $teacher = \App\Models\Teacher::where('email', $user?->email)->first() 
            ?? \App\Models\Teacher::find(3) 
            ?? \App\Models\Teacher::first();
            
        if ($teacher) {
            // My Classes
            $teacherClassIds = \App\Models\Course::where('teacher_id', $teacher->id)->pluck('school_class_id')->unique()->toArray();
            $myClassesCount = count($teacherClassIds);
            
            // My Students
            $myStudentsCount = !empty($teacherClassIds) 
                ? \App\Models\Student::whereIn('school_class_id', $teacherClassIds)->count() 
                : 0;
                
            // Homework to Grade
            $assessmentIds = \App\Models\ClassroomAssessment::where('teacher_id', $teacher->id)->pluck('id')->toArray();
            $homeworkToGradeCount = !empty($assessmentIds)
                ? \App\Models\ClassroomSubmission::whereIn('classroom_assessment_id', $assessmentIds)
                    ->where('status', 'À corriger')
                    ->count()
                : 0;
                
            // Attendance Rate for this teacher's classes
            if (!empty($teacherClassIds)) {
                $totalTeacherAttendance = \App\Models\Attendance::whereIn('school_class_id', $teacherClassIds)->count();
                if ($totalTeacherAttendance > 0) {
                    $teacherPresentCount = \App\Models\Attendance::whereIn('school_class_id', $teacherClassIds)
                        ->whereIn('status', ['present', 'late'])
                        ->count();
                    $teacherAttendanceRate = round(($teacherPresentCount / $totalTeacherAttendance) * 100, 1) . '%';
                }
            }
        }
    }
@endphp
<div id="proDashboardData" 
     data-role="{{ $role }}" 
     data-show-revenues="{{ ($isAdmin || $isFinance) ? 1 : 0 }}" 
     data-can-edit-student="{{ ($isAdmin || $isSecretary) ? 1 : 0 }}" 
     data-can-see-options="{{ ($isAdmin || $isFinance || $isSecretary) ? 1 : 0 }}" 
     data-chart-months="{{ json_encode($months) }}"
     data-chart-admissions="{{ json_encode($admissionsCounts) }}"
     data-chart-revenues="{{ json_encode($revenuesAmounts) }}"
     class="space-y-6 animate-fade-in pb-12">

    <!-- HERO SECTION -->
    <div class="relative overflow-hidden rounded-[24px] glass-panel border-0 shadow-lg mesh-bg text-white p-8">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold tracking-wide mb-4 border border-white/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Système Opérationnel · {{ $role }}
                </div>
                <h1 class="text-3xl md:text-4xl font-bold font-space mb-2">Bonjour, {{ $user ? explode(' ', $user->name)[0] : 'Invité' }} 👋</h1>
                @if ($isAdmin)
                    <p class="text-indigo-100 max-w-xl text-sm md:text-base">Voici un résumé de l'activité générale de l'établissement aujourd'hui. Il y a 12 nouvelles demandes d'admission en attente de votre validation.</p>
                @elseif ($isFinance)
                    <p class="text-indigo-100 max-w-xl text-sm md:text-base">Voici un résumé de la situation financière de l'établissement. Vous avez des paiements de frais scolaires en attente de traitement.</p>
                @elseif ($isTeacher)
                    <p class="text-indigo-100 max-w-xl text-sm md:text-base">Bienvenue dans votre espace enseignant. Voici un aperçu de vos cours, de la présence et des devoirs de vos classes.</p>
                @elseif ($isHR)
                    <p class="text-indigo-100 max-w-xl text-sm md:text-base">Bienvenue dans votre espace RH. Vous pouvez suivre la présence du personnel, gérer les congés et les dossiers administratifs.</p>
                @else
                    <p class="text-indigo-100 max-w-xl text-sm md:text-base">Voici un résumé de l'activité de l'établissement aujourd'hui.</p>
                @endif
            </div>
            
            <div class="flex gap-3">
                @if ($isAdmin || $isSecretary)
                    <button class="px-5 py-2.5 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 duration-200">
                        Gérer les admissions
                    </button>
                @elseif ($isFinance)
                    <button class="px-5 py-2.5 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 duration-200">
                        Voir les encaissements
                    </button>
                @elseif ($isTeacher)
                    <button class="px-5 py-2.5 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 duration-200">
                        Saisir les présences
                    </button>
                @elseif ($isHR)
                    <button class="px-5 py-2.5 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 duration-200">
                        Gérer le personnel
                    </button>
                @else
                    <button class="px-5 py-2.5 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 duration-200">
                        Voir le profil
                    </button>
                @endif
                <button class="p-2.5 bg-white/20 backdrop-blur-md text-white rounded-xl hover:bg-white/30 transition-colors border border-white/20">
                    <i data-lucide="download" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Decorative Shapes -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-32 w-48 h-48 bg-purple-500 opacity-20 rounded-full blur-2xl"></div>
    </div>

    <!-- STATS WIDGETS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        @if ($isAdmin)
            <!-- Stat Card 1 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Élèves</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ number_format($totalStudentsCount) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +12%
                    </span>
                    <span class="text-slate-400 text-xs">vs mois dernier</span>
                </div>
            </div>

            <!-- Stat Card 2 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Revenus (Mensuel)</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">45,200€</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +8.5%
                    </span>
                    <span class="text-slate-400 text-xs">vs mois dernier</span>
                </div>
            </div>

            <!-- Stat Card 3 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Admissions en attente</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">48</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i data-lucide="file-plus" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-4">
                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: 45%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2">45% de l'objectif mensuel</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Taux de Présence</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $globalAttendanceRate }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-rose-600 font-medium bg-rose-50 dark:bg-rose-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-down" class="w-3 h-3"></i> -1.2%
                    </span>
                    <span class="text-slate-400 text-xs">Aujourd'hui</span>
                </div>
            </div>

        @elseif ($isFinance)
            <!-- Stat Card 1 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Élèves</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ number_format($totalStudentsCount) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +12%
                    </span>
                    <span class="text-slate-400 text-xs">vs mois dernier</span>
                </div>
            </div>

            <!-- Stat Card 2 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Revenus (Mensuel)</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">45,200€</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +8.5%
                    </span>
                    <span class="text-slate-400 text-xs">vs mois dernier</span>
                </div>
            </div>

            <!-- Stat Card 3 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Paiements en Attente</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">32</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-4">
                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: 68%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2">68% du recouvrement visé</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Taux de Recouvrement</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">88.4%</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="percent" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +3.1%
                    </span>
                    <span class="text-slate-400 text-xs">Moyenne trimestrielle</span>
                </div>
            </div>

        @elseif ($isTeacher)
            <!-- Stat Card 1 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Mes Classes</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $myClassesCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Classes actives affectées</p>
            </div>

            <!-- Stat Card 2 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Mes Élèves</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $myStudentsCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Effectif total d'élèves encadrés</p>
            </div>

            <!-- Stat Card 3 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Devoirs à corriger</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">{{ $homeworkToGradeCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-4">
                    <div class="bg-rose-500 h-1.5 rounded-full" @style(['width: ' . ($homeworkToGradeCount > 0 ? min(($homeworkToGradeCount / 10) * 100, 100) : 0) . '%'])></div>
                </div>
                <p class="text-xs text-slate-400 mt-2">Urgent : Rendu sous 48h</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Taux de Présence Moyen</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $teacherAttendanceRate }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> Stable
                    </span>
                    <span class="text-slate-400 text-xs">Moyenne globale classes</span>
                </div>
            </div>

        @elseif ($isHR)
            <!-- Stat Card 1 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Personnel</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">78</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Enseignants & agents actifs</p>
            </div>

            <!-- Stat Card 2 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Présence Personnel</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">98.2%</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Présences aujourd'hui</p>
            </div>

            <!-- Stat Card 3 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Congés en attente</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">4</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-4">
                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: 50%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2">En attente de validation</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Entretiens prévus</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">3</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Cette semaine (Recrutement)</p>
            </div>

        @else
            <!-- Stat Card 1 (Secretaire & Default) -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Élèves</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ number_format($totalStudentsCount) }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="flex items-center gap-1 text-emerald-600 font-medium bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +12%
                    </span>
                    <span class="text-slate-400 text-xs">vs mois dernier</span>
                </div>
            </div>

            <!-- Stat Card 2 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Admissions en attente</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">48</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i data-lucide="file-plus" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-4">
                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: 45%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-2">Objectifs mensuels</p>
            </div>

            <!-- Stat Card 3 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Taux de Présence</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $globalAttendanceRate }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Aujourd'hui</p>
            </div>

            <!-- Stat Card 4 -->
            <div class="glass-panel p-5 group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Évènements à venir</p>
                        <h3 class="text-2xl font-bold font-space text-slate-900 dark:text-white mt-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">5</h3>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Cette semaine</p>
            </div>
        @endif

    </div>

    <!-- CHARTS & MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Chart: Inscriptions / Revenus (ApexCharts) -->
        <div class="lg:col-span-2 glass-panel p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">
                        {{ ($isAdmin || $isFinance) ? 'Évolution des Admissions & Revenus' : 'Évolution des Admissions' }}
                    </h3>
                    <p class="text-sm text-slate-500">
                        {{ ($isAdmin || $isFinance) ? 'Statistiques sur les 6 derniers mois' : 'Statistiques d\'admissions sur les 6 derniers mois' }}
                    </p>
                </div>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="p-2 text-slate-400 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-900/30 rounded-lg transition-colors border border-slate-200 dark:border-slate-700">
                        <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 origin-top-right bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 py-1.5 z-10 text-sm focus:outline-none"
                         style="display: none;">
                        <a href="/admin/pro-admissions" class="flex items-center gap-2.5 px-4 py-2.5 text-slate-700 dark:text-slate-200 hover:bg-indigo-50/50 dark:hover:bg-slate-700/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors font-medium">
                            <i data-lucide="eye" class="w-4 h-4 text-slate-400"></i>
                            Voir les admissions
                        </a>
                        <button onclick="window.location.reload()" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-slate-700 dark:text-slate-200 hover:bg-indigo-50/50 dark:hover:bg-slate-700/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-left font-medium">
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400"></i>
                            Actualiser
                        </button>
                    </div>
                </div>
            </div>
            <div id="mainChart" class="w-full h-72"></div>
        </div>

        <!-- Side Widget: Upcoming Events -->
        <div class="glass-panel p-6 flex flex-col">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">Prochains Évènements</h3>
            </div>
            
            <div class="space-y-5 flex-1">
                <div class="flex gap-4">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 border border-indigo-100 dark:border-indigo-900/50">
                        <span class="text-xs font-semibold uppercase">Oct</span>
                        <span class="text-lg font-bold leading-none">12</span>
                    </div>
                    <div>
                        <h4 class="font-medium text-slate-900 dark:text-white text-sm">Conseil de classe 3ème A</h4>
                        <p class="text-xs text-slate-500 mt-1">14:00 - Salle 204</p>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        <span class="text-xs font-semibold uppercase">Oct</span>
                        <span class="text-lg font-bold leading-none">15</span>
                    </div>
                    <div>
                        <h4 class="font-medium text-slate-900 dark:text-white text-sm">Date limite paiement T1</h4>
                        <p class="text-xs text-slate-500 mt-1">Scolarité générale</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        <span class="text-xs font-semibold uppercase">Oct</span>
                        <span class="text-lg font-bold leading-none">18</span>
                    </div>
                    <div>
                        <h4 class="font-medium text-slate-900 dark:text-white text-sm">Réunion Parents-Profs</h4>
                        <p class="text-xs text-slate-500 mt-1">18:00 - Gymnase</p>
                    </div>
                </div>
            </div>
            
            <button class="w-full py-2.5 mt-4 text-sm font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-xl transition-colors border border-indigo-100 dark:border-indigo-900/50">
                Voir le calendrier complet
            </button>
        </div>

    </div>

    <!-- RECENT DATA TABLE (Custom Premium Design) -->
    <div class="glass-panel overflow-hidden">
        <!-- Table Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-6 pt-6 pb-4 gap-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-bold font-space text-slate-900 dark:text-white">Dernières Inscriptions</h3>
                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">128</span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Élèves récemment inscrits dans le système</p>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <!-- Filters -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 mr-1">
                    <button id="filterAll" onclick="filterTable('all')" class="px-2.5 py-1 rounded-md text-xs font-medium transition-all bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white">Tous</button>
                    <button id="filterInscrit" onclick="filterTable('Inscrit')" class="px-2.5 py-1 rounded-md text-xs font-medium transition-all text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Inscrits</button>
                    <button id="filterPending" onclick="filterTable('En attente')" class="px-2.5 py-1 rounded-md text-xs font-medium transition-all text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">En attente</button>
                </div>
                <!-- Search -->
                <div class="relative flex-1 sm:w-56">
                    <input type="text" id="tableSearch" oninput="searchTable(this.value)" class="block w-full py-2 pl-9 pr-3 text-sm text-slate-900 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-900 dark:border-slate-700 dark:placeholder-slate-500 dark:text-white transition-all" placeholder="Chercher...">
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <!-- Export -->
                <button class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Export
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50">
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <button class="flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                                Élève
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" /></svg>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Classe</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">Date d'inscription</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <!-- Rows injected by JS -->
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/20">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Affichage de <span class="font-medium text-slate-700 dark:text-slate-300" id="startIndex">0</span> à <span class="font-medium text-slate-700 dark:text-slate-300" id="endIndex">0</span> résultats sur <span class="font-medium text-slate-700 dark:text-slate-300" id="totalCount">0</span>
            </p>
            <div class="flex items-center gap-1" id="paginationContainer">
                <!-- Dynamically injected by JavaScript -->
            </div>
        </div>
    </div>


</div>

<!-- Scripts for Charts and Tables -->
<script>
// Read data attributes from the container to keep script syntax clean for static analysis
const dataEl = document.getElementById('proDashboardData');
const userRole = dataEl ? dataEl.getAttribute('data-role') : 'Enseignant';
const showRevenues = dataEl ? dataEl.getAttribute('data-show-revenues') === '1' : false;
const canEditStudent = dataEl ? dataEl.getAttribute('data-can-edit-student') === '1' : false;
const canSeeOptions = dataEl ? dataEl.getAttribute('data-can-see-options') === '1' : false;

const chartMonths = dataEl && dataEl.getAttribute('data-chart-months') ? JSON.parse(dataEl.getAttribute('data-chart-months')) : ['Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct'];
const chartAdmissions = dataEl && dataEl.getAttribute('data-chart-admissions') ? JSON.parse(dataEl.getAttribute('data-chart-admissions')) : [0, 0, 0, 0, 0, 0];
const chartRevenues = dataEl && dataEl.getAttribute('data-chart-revenues') ? JSON.parse(dataEl.getAttribute('data-chart-revenues')) : [0, 0, 0, 0, 0, 0];

// =========================================
// TABLE DATA & STATE
// =========================================
const allStudents = [
    { id: 1, name: "Léa Dubois",    matricule: "MAT-2024-001", class: "3ème A",     status: "Inscrit",    date: "12 Oct 2023", bg: "e879f9" },
    { id: 2, name: "Thomas Leroy",  matricule: "MAT-2024-002", class: "6ème B",     status: "En attente", date: "11 Oct 2023", bg: "60a5fa" },
    { id: 3, name: "Emma Moreau",   matricule: "MAT-2024-003", class: "Terminale S",status: "Inscrit",    date: "10 Oct 2023", bg: "34d399" },
    { id: 4, name: "Hugo Blanc",    matricule: "MAT-2024-004", class: "4ème C",     status: "Inscrit",    date: "09 Oct 2023", bg: "fbbf24" },
    { id: 5, name: "Chloé Girard",  matricule: "MAT-2024-005", class: "5ème A",     status: "En attente", date: "08 Oct 2023", bg: "a78bfa" },
    { id: 6, name: "Lucas Bernard", matricule: "MAT-2024-006", class: "2nde A",     status: "Inscrit",    date: "07 Oct 2023", bg: "fb923c" },
    { id: 7, name: "Inès Simon",    matricule: "MAT-2024-007", class: "1ère B",     status: "En attente", date: "06 Oct 2023", bg: "f87171" },
];

let currentFilter = 'all';
let currentSearch = '';
let currentPage = 1;
const pageSize = 5;

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function statusBadge(status) {
    if (status === 'Inscrit') {
        return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>Inscrit
                </span>`;
    }
    return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-900/40">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0 animate-pulse"></span>En attente
            </span>`;
}

function renderTable() {
    const tbody = document.getElementById('studentsTableBody');
    const startEl = document.getElementById('startIndex');
    const endEl = document.getElementById('endIndex');
    const totalEl = document.getElementById('totalCount');
    const paginationContainer = document.getElementById('paginationContainer');
    if (!tbody) return;

    let data = allStudents;
    if (currentFilter !== 'all') data = data.filter(s => s.status === currentFilter);
    if (currentSearch) data = data.filter(s => s.name.toLowerCase().includes(currentSearch.toLowerCase()) || s.matricule.toLowerCase().includes(currentSearch.toLowerCase()));

    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / pageSize) || 1;

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = totalItems > 0 ? (currentPage - 1) * pageSize + 1 : 0;
    const endIdx = Math.min(currentPage * pageSize, totalItems);

    if (startEl) startEl.textContent = startIdx;
    if (endEl) endEl.textContent = endIdx;
    if (totalEl) totalEl.textContent = totalItems;

    const paginatedData = data.slice(startIdx - 1, endIdx);

    if (paginatedData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Aucun élève trouvé
            </div>
        </td></tr>`;
        if (paginationContainer) paginationContainer.innerHTML = '';
        return;
    }

    tbody.innerHTML = paginatedData.map((s, index) => {
        let actionButtons = `
            <button class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="Voir le profil">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
        `;
        if (canEditStudent) {
            actionButtons += `
                <button class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors" title="Modifier">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            `;
        }
        if (canSeeOptions) {
            actionButtons += `
                <button class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors" title="Plus d'options">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                </button>
            `;
        }

        return `
            <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors duration-150" style="animation: fadeIn 0.3s ease forwards; animation-delay: ${index * 40}ms; opacity:0;">
                <td class="px-6 py-4">
                    <input type="checkbox" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500">
                </td>
                <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-shrink-0">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm" 
                                 style="background: linear-gradient(135deg, #${s.bg}, #${s.bg}99);">
                                ${getInitials(s.name)}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white dark:border-[#0a0a0a] ${s.status === 'Inscrit' ? 'bg-emerald-400' : 'bg-amber-400'}"></span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-white text-sm leading-none">${s.name}</p>
                            <p class="text-xs text-slate-400 mt-1 font-mono">${s.matricule}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium border border-slate-200 dark:border-slate-700">
                        ${s.class}
                    </span>
                </td>
                <td class="px-4 py-4">${statusBadge(s.status)}</td>
                <td class="px-4 py-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">${s.date}</p>
                </td>
                <td class="px-4 py-4 text-right">
                    <div class="flex items-center justify-end gap-1 transition-opacity duration-200">
                        ${actionButtons}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    // Render pagination buttons dynamically
    if (paginationContainer) {
        let buttonsHtml = '';

        // Previous button
        const prevDisabled = currentPage === 1;
        buttonsHtml += `
            <button onclick="${prevDisabled ? '' : `changePage(${currentPage - 1})`}" 
                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-white dark:hover:bg-slate-800 disabled:opacity-40 disabled:pointer-events-none transition-all" 
                    ${prevDisabled ? 'disabled' : ''}>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
        `;

        // Page numbers
        for (let p = 1; p <= totalPages; p++) {
            const isActive = p === currentPage;
            buttonsHtml += `
                <button onclick="changePage(${p})" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold ${isActive ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-800'} transition-all">
                    ${p}
                </button>
            `;
        }

        // Next button
        const nextDisabled = currentPage === totalPages;
        buttonsHtml += `
            <button onclick="${nextDisabled ? '' : `changePage(${currentPage + 1})`}" 
                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-white dark:hover:bg-slate-800 disabled:opacity-40 disabled:pointer-events-none transition-all" 
                    ${nextDisabled ? 'disabled' : ''}>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        `;

        paginationContainer.innerHTML = buttonsHtml;
    }
}

window.changePage = function(page) {
    currentPage = page;
    renderTable();
};

function filterTable(filter) {
    currentFilter = filter;
    currentPage = 1; // Reset to page 1
    // Update active button styling
    ['filterAll', 'filterInscrit', 'filterPending'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.className = 'px-2.5 py-1 rounded-md text-xs font-medium transition-all text-slate-500 hover:text-slate-700 dark:hover:text-slate-300';
    });
    const activeMap = { all: 'filterAll', 'Inscrit': 'filterInscrit', 'En attente': 'filterPending' };
    const activeBtn = document.getElementById(activeMap[filter]);
    if (activeBtn) activeBtn.className = 'px-2.5 py-1 rounded-md text-xs font-medium transition-all bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white';
    renderTable();
}

function searchTable(value) {
    currentSearch = value;
    currentPage = 1; // Reset to page 1
    renderTable();
}

// =========================================
// APEXCHARTS
// =========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Initial render table
    renderTable();

    // ApexCharts series conditional logic
    const chartSeries = [{
        name: 'Admissions',
        type: 'column',
        data: chartAdmissions
    }];

    if (showRevenues) {
        chartSeries.push({
            name: 'Revenus (K€)',
            type: 'area',
            data: chartRevenues
        });
    }

    const yaxisOptions = [
        { labels: { style: { colors: '#94a3b8', fontSize: '12px' } } }
    ];

    if (showRevenues) {
        yaxisOptions.push({ opposite: true, labels: { style: { colors: '#94a3b8', fontSize: '12px' } } });
    }

    const chartOptions = {
        series: chartSeries,
        chart: {
            height: 300,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        colors: showRevenues ? ['#4f46e5', '#10b981'] : ['#4f46e5'],
        stroke: { width: showRevenues ? [0, 3] : [0], curve: 'smooth' },
        fill: {
            type: showRevenues ? ['solid', 'gradient'] : ['solid'],
            gradient: {
                shade: 'light', type: "vertical",
                opacityFrom: 0.4, opacityTo: 0.02,
            }
        },
        dataLabels: { enabled: false },
        labels: chartMonths,
        xaxis: {
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#94a3b8', fontSize: '12px' } }
        },
        yaxis: yaxisOptions,
        grid: { borderColor: 'rgba(148, 163, 184, 0.08)', strokeDashArray: 4 },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px' }
    };

    const chart = new ApexCharts(document.querySelector("#mainChart"), chartOptions);
    chart.render();

    // Watch for dark class changes on <html> via MutationObserver — instant sync
    const observer = new MutationObserver(() => {
        const isDark = document.documentElement.classList.contains('dark');
        chart.updateOptions({ 
            theme: { mode: isDark ? 'dark' : 'light' }, 
            chart: { background: 'transparent' },
            grid: { borderColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(148, 163, 184, 0.08)' }
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
</script>
