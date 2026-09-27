<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use App\Models\HrConflict;
use App\Models\Timesheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ProHRAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-analytics';
    protected static ?string $title = 'Reporting & Analyses';

    protected static bool $shouldRegisterNavigation = false;

    // Report Form Properties
    public $report_title;
    public $report_content;

    // Dashboard Statistics
    public $stats = [];

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        
        $this->report_title = "Rapport d'Analyses & Climat Social - " . Carbon::now()->translatedFormat('F Y');
        
        // Initializing default report template
        $this->report_content = "<h2>1. Climat Social & Coopération</h2>
<p>L'analyse générale montre une bonne cohésion globale au sein des équipes pédagogiques et administratives. Cependant, nous restons attentifs aux résolutions des récents incidents déclarés.</p>
<h2>2. Synthèse des Effectifs & Rémunération</h2>
<p>L'équipe se renforce avec une gestion rigoureuse de la masse salariale. Le salaire moyen reste compétitif par rapport au marché de l'éducation nationale.</p>
<h2>3. Absentéisme & Gestion des Congés</h2>
<p>Les demandes de congés maladie et congés sans solde ont été traitées conformément à la politique interne. Le taux de présence global est satisfaisant pour la période en cours.</p>";

        $this->loadStats();
    }

    public function loadStats()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        $activeCount = Teacher::where('status', 'Actif')->count();
        $totalSalary = Teacher::where('status', 'Actif')->sum('salary');
        $avgSalary = Teacher::where('status', 'Actif')->avg('salary') ?: 0;

        // Specialty Breakdown
        $specialties = Teacher::select('specialty', DB::raw('count(*) as count'))
            ->whereNotNull('specialty')
            ->where('specialty', '!=', '')
            ->groupBy('specialty')
            ->get()
            ->toArray();

        // Leave Requests Stats
        $leavesCount = LeaveRequest::count();
        $approvedLeaves = LeaveRequest::where('status', 'Approuvé')->count();

        // Conflict Stats
        $conflictsCount = HrConflict::count();
        $activeConflicts = HrConflict::where('status', 'En cours')->count();
        $criticalConflicts = HrConflict::where('severity', 'Critique')->count();

        // Timesheet / Presence rate
        $expectedDays = $activeCount * 20; // 20 working days per active teacher
        $actualPresences = Timesheet::whereBetween('date', [$startOfMonth, $endOfMonth])->count();
        $presenceRate = $expectedDays > 0 ? min(100, round(($actualPresences / $expectedDays) * 100)) : 100;

        $this->stats = [
            'total_employees' => Teacher::count(),
            'active_employees' => $activeCount,
            'candidats' => Teacher::where('status', 'Candidat')->count(),
            'leaves_count' => Teacher::where('status', 'En congé')->count(),
            'anciens_count' => Teacher::where('status', 'Ancien')->count(),
            'total_salary' => $totalSalary,
            'avg_salary' => round($avgSalary, 2),
            'specialties' => $specialties,
            'presence_rate' => $presenceRate,
            'leaves_total' => $leavesCount,
            'leaves_approved' => $approvedLeaves,
            'conflicts_total' => $conflictsCount,
            'conflicts_active' => $activeConflicts,
            'conflicts_critical' => $criticalConflicts
        ];
    }

    public function exportReportPdf()
    {
        $this->loadStats();

        $data = [
            'title' => $this->report_title,
            'content' => $this->report_content,
            'stats' => $this->stats,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'author' => Auth::user()->name
        ];

        return response()->streamDownload(function () use ($data) {
            $pdf = Pdf::loadView('pdf.hr-report', $data);
            echo $pdf->output();
        }, 'rapport-rh-' . date('Y-m-d') . '.pdf');
    }
}
