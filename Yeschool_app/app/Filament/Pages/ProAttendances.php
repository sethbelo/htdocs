<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class ProAttendances extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-attendances';
    protected static ?string $title = 'Présences & Alertes';
    protected static bool $shouldRegisterNavigation = false; // Custom sidebar link used instead

    // Tabs
    public $activeTab = 'appel'; // 'appel', 'statistiques', 'logs', 'whatsapp_settings'

    // Data lists
    public $schoolClasses = [];
    public $studentsList = [];
    
    // Filters for Appel
    public $selectedClassId = null;
    public $selectedDate = null;
    public $importFile;
    
    // Form inputs for daily sheet
    public $attendanceInput = []; // [student_id => ['status' => 'present', 'reason' => '', 'is_justified' => false]]
    public $autoSendWhatsApp = true; // Auto-send alerts to parents on save for absences/lates

    // Statistics & Analytics
    public $attendanceRateWeek = 0;
    public $attendanceRateMonth = 0;
    public $attendanceRateYear = 0;
    
    public $totalPresentYear = 0;
    public $totalAbsentYear = 0;
    public $totalLateYear = 0;
    public $totalJustifiedYear = 0;

    public $weeklyTrendData = []; // ['dates' => [], 'rates' => []]
    public $classComparisonData = []; // ['classes' => [], 'rates' => []]
    public $studentReportData = []; // [student_id => ['name' => '', 'present' => x, 'absent' => y, 'late' => z, 'justified' => w]]
    public $filteredStudentReport = [];

    // Monthly Grid Calendar properties
    public $daysInSelectedMonth = 0;
    public $monthDaysList = [];
    public $monthlyGridData = [];
    public $monthlySearch = '';

    // Search query for report
    public $reportSearch = '';

    // WhatsApp configuration
    public $whatsapp_api_enabled;
    public $whatsapp_api_url;
    public $whatsapp_api_token;
    public $whatsapp_message_template;
    public $whatsapp_simulation_logs = [];

    protected $rules = [
        'whatsapp_api_url' => 'nullable|url',
        'whatsapp_api_token' => 'nullable|string',
        'whatsapp_message_template' => 'required|string',
    ];

    public function getIsHeadTeacherProperty()
    {
        if (!Auth::user()->hasRole('Enseignant')) {
            return true;
        }
        $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
        if (!$teacher) {
            return false;
        }
        return SchoolClass::where('head_teacher_id', $teacher->id)->exists();
    }

    public function mount()
    {
        $this->selectedDate = date('Y-m-d');
        
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $this->schoolClasses = SchoolClass::where('head_teacher_id', $teacherId)->orderBy('level')->orderBy('name')->get()->toArray();
        } else {
            $this->schoolClasses = SchoolClass::orderBy('level')->orderBy('name')->get()->toArray();
        }

        if (!empty($this->schoolClasses)) {
            $this->selectedClassId = $this->schoolClasses[0]['id'];
        } else {
            $this->selectedClassId = null;
        }

        $this->loadAttendanceSheet();
        $this->loadWhatsAppSettings();
        $this->calculateStats();
        $this->loadMonthlyGridData();
    }

    public function updatedSelectedClassId()
    {
        if (Auth::user()->hasRole('Enseignant') && $this->selectedClassId) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $isTitulaire = SchoolClass::where('id', $this->selectedClassId)
                ->where('head_teacher_id', $teacherId)
                ->exists();
            if (!$isTitulaire) {
                $this->selectedClassId = null;
            }
        }

        $this->loadAttendanceSheet();
        $this->calculateStats();
        $this->loadMonthlyGridData();
    }

    public function updatedSelectedDate()
    {
        $this->loadAttendanceSheet();
        $this->calculateStats();
        $this->loadMonthlyGridData();
    }
    public function updatedMonthlySearch()
    {
        $this->loadMonthlyGridData();
    }

    public function updatedActiveTab()
    {
        if ($this->activeTab === 'whatsapp_settings' && !Auth::user()->hasRole('Super Admin')) {
            $this->activeTab = 'appel';
            return;
        }
        if ($this->activeTab === 'logs' && !$this->is_head_teacher) {
            $this->activeTab = 'appel';
            return;
        }
        if ($this->activeTab === 'statistiques') {
            $this->calculateStats();
        } elseif ($this->activeTab === 'logs') {
            $this->loadWhatsAppLogs();
        } elseif ($this->activeTab === 'grille_mensuelle') {
            $this->loadMonthlyGridData();
        }
    }

    /**
     * Load the list of students for the selected class and their existing attendance for the selected date.
     */
    public function loadAttendanceSheet()
    {
        if (!$this->selectedClassId) {
            $this->studentsList = [];
            return;
        }

        // Get students in this class
        $this->studentsList = Student::where('school_class_id', $this->selectedClassId)
            ->where('status', 'Active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()->toArray();

        // Get existing attendances for the selected date
        $existing = Attendance::where('school_class_id', $this->selectedClassId)
            ->where('date', $this->selectedDate)
            ->get()
            ->keyBy('student_id');

        $this->attendanceInput = [];
        foreach ($this->studentsList as $student) {
            $att = $existing->get($student['id']);
            
            $this->attendanceInput[$student['id']] = [
                'status' => $att ? $att->status : 'present', // Defaults to present
                'reason' => $att ? $att->reason : '',
                'is_justified' => $att ? (bool)$att->is_justified : false,
                'whatsapp_sent' => $att ? (bool)$att->whatsapp_sent : false,
                'whatsapp_log' => $att ? $att->whatsapp_log : '',
            ];
        }
    }

    /**
     * Save the attendance sheet and optionally send WhatsApp alerts.
     */
    public function saveAttendances()
    {
        if (!$this->selectedClassId) return;

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $isTitulaire = SchoolClass::where('id', $this->selectedClassId)
                ->where('head_teacher_id', $teacherId)
                ->exists();
            if (!$isTitulaire) {
                abort(403, 'Accès non autorisé.');
            }
        }

        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            $selectedYearId = AcademicYear::where('is_active', true)->first()?->id 
                ?? AcademicYear::first()?->id;
        }

        $dateFormatted = Carbon::parse($this->selectedDate)->format('d/m/Y');
        $className = SchoolClass::find($this->selectedClassId)?->name ?? 'Classe';

        $alertsSent = 0;
        $alertsFailed = 0;

        foreach ($this->attendanceInput as $studentId => $data) {
            $student = Student::find($studentId);
            if (!$student) continue;

            // Fetch existing attendance to check if status changed to avoid duplicate automatic whatsapp sends
            $existingAtt = Attendance::where('student_id', $studentId)
                ->where('date', $this->selectedDate)
                ->first();

            $statusChanged = !$existingAtt || ($existingAtt->status !== $data['status']);

            // Save to DB
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date' => $this->selectedDate,
                ],
                [
                    'school_class_id' => $this->selectedClassId,
                    'academic_year_id' => $selectedYearId,
                    'status' => $data['status'],
                    'reason' => $data['status'] === 'present' ? null : $data['reason'],
                    'is_justified' => $data['status'] === 'present' ? false : $data['is_justified'],
                ]
            );

            // Check if we need to send WhatsApp alert (only for absent or late, and if enabled)
            if ($this->autoSendWhatsApp && $statusChanged && in_array($data['status'], ['absent', 'late'])) {
                if (!empty($student->parent_phone)) {
                    $msg = WhatsAppService::buildTemplate(
                        $student->parent_name,
                        "{$student->first_name} {$student->last_name}",
                        $className,
                        $data['status'],
                        $dateFormatted,
                        $data['reason']
                    );

                    $result = WhatsAppService::sendMessage($student->parent_phone, $msg);

                    // Update attendance record with whatsapp sending status
                    $attendance->update([
                        'whatsapp_sent' => $result['success'],
                        'whatsapp_log' => $result['log']
                    ]);

                    if ($result['success']) {
                        $alertsSent++;
                    } else {
                        $alertsFailed++;
                    }
                } else {
                    $attendance->update([
                        'whatsapp_log' => "Numéro WhatsApp du parent non renseigné."
                    ]);
                }
            }
        }

        // Reload sheet and recalculate statistics
        $this->loadAttendanceSheet();
        $this->calculateStats();

        // Flash message
        $msgString = "Feuille de présence enregistrée avec succès.";
        if ($alertsSent > 0) {
            $msgString .= " {$alertsSent} alerte(s) WhatsApp envoyée(s).";
        }
        if ($alertsFailed > 0) {
            $msgString .= " {$alertsFailed} alerte(s) échouée(s).";
        }
        session()->flash('success', $msgString);
    }

    /**
     * Send a single WhatsApp alert manually for a student's attendance on the selected date.
     */
    public function sendSingleWhatsApp($studentId)
    {
        $student = Student::find($studentId);
        $className = SchoolClass::find($this->selectedClassId)?->name ?? 'Classe';
        $dateFormatted = Carbon::parse($this->selectedDate)->format('d/m/Y');
        
        $data = $this->attendanceInput[$studentId] ?? null;

        if (!$student || !$data || !in_array($data['status'], ['absent', 'late'])) {
            session()->flash('error', "Impossible d'envoyer l'alerte pour cet état de présence.");
            return;
        }

        if (empty($student->parent_phone)) {
            session()->flash('error', "Le numéro de téléphone du parent n'est pas configuré pour {$student->first_name}.");
            return;
        }

        $msg = WhatsAppService::buildTemplate(
            $student->parent_name,
            "{$student->first_name} {$student->last_name}",
            $className,
            $data['status'],
            $dateFormatted,
            $data['reason']
        );

        $result = WhatsAppService::sendMessage($student->parent_phone, $msg);

        // Update record in database
        Attendance::where('student_id', $studentId)
            ->where('date', $this->selectedDate)
            ->update([
                'whatsapp_sent' => $result['success'],
                'whatsapp_log' => $result['log']
            ]);

        // Reload and notify
        $this->loadAttendanceSheet();

    }

    /**
     * Download a clean CSV/Excel template for the selected class.
     */
    public function downloadTemplate()
    {
        if (!$this->selectedClassId) return;

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $isTitulaire = SchoolClass::where('id', $this->selectedClassId)
                ->where('head_teacher_id', $teacherId)
                ->exists();
            if (!$isTitulaire) {
                abort(403, 'Accès non autorisé.');
            }
        }

        $className = SchoolClass::find($this->selectedClassId)?->name ?? 'Classe';
        $filename = 'modele_import_appel_' . str_replace(' ', '_', $className) . '.csv';

        return response()->streamDownload(function () {
            // Write BOM for Excel
            echo "\xEF\xBB\xBF";
            
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'ID Élève',
                'Matricule',
                'Nom',
                'Prénom',
                'Présence (present/absent/late)',
                'Motif',
                'Justifié (oui/non)'
            ], ';');

            foreach ($this->studentsList as $student) {
                fputcsv($file, [
                    $student['id'],
                    $student['matricule'],
                    $student['last_name'],
                    $student['first_name'],
                    'present', // default
                    '', // empty reason
                    'non' // default justification
                ], ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export the attendance call sheet to CSV/Excel format.
     */
    public function exportAttendance()
    {
        if (!$this->selectedClassId) return;

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $isTitulaire = SchoolClass::where('id', $this->selectedClassId)
                ->where('head_teacher_id', $teacherId)
                ->exists();
            if (!$isTitulaire) {
                abort(403, 'Accès non autorisé.');
            }
        }

        $className = SchoolClass::find($this->selectedClassId)?->name ?? 'Classe';
        $date = $this->selectedDate;
        
        $filename = 'liste_appel_' . str_replace(' ', '_', $className) . '_' . $date . '.csv';

        return response()->streamDownload(function () {
            // Write BOM for Excel
            echo "\xEF\xBB\xBF";
            
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'ID Élève',
                'Matricule',
                'Nom',
                'Prénom',
                'Présence (present/absent/late)',
                'Motif',
                'Justifié (oui/non)'
            ], ';');

            foreach ($this->studentsList as $student) {
                $att = $this->attendanceInput[$student['id']] ?? null;
                $status = $att ? $att['status'] : 'present';
                $reason = $att ? $att['reason'] : '';
                $justified = ($att && $att['is_justified']) ? 'oui' : 'non';

                fputcsv($file, [
                    $student['id'],
                    $student['matricule'],
                    $student['last_name'],
                    $student['first_name'],
                    $status,
                    $reason,
                    $justified
                ], ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Import attendance call sheet from uploaded CSV/Excel.
     */
    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'required|file|max:1024',
        ]);

        $path = $this->importFile->getRealPath();
        
        // Read file
        $file = fopen($path, 'r');
        
        // Skip BOM if present
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }

        // Read header
        $header = fgetcsv($file, 0, ';');
        
        $importedCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) < 5) {
                continue;
            }

            $studentId = intval($row[0]);
            $status = trim(strtolower($row[4]));
            $reason = isset($row[5]) ? trim($row[5]) : '';
            $justifiedText = isset($row[6]) ? trim(strtolower($row[6])) : 'non';
            $isJustified = in_array($justifiedText, ['oui', 'yes', '1', 'vrai', 'true']);

            // Validate status
            if (!in_array($status, ['present', 'absent', 'late'])) {
                $status = 'present';
            }

            // Check if student exists in our current list
            $studentExists = false;
            foreach ($this->studentsList as $student) {
                if ($student['id'] === $studentId) {
                    $studentExists = true;
                    break;
                }
            }

            if ($studentExists) {
                $this->attendanceInput[$studentId] = [
                    'status' => $status,
                    'reason' => $status === 'present' ? '' : $reason,
                    'is_justified' => $status === 'present' ? false : $isJustified,
                    'whatsapp_sent' => false,
                    'whatsapp_log' => 'Importé par fichier CSV',
                ];
                $importedCount++;
            } else {
                $errorCount++;
            }
        }
        
        fclose($file);
        
        // Clean temporary upload file
        $this->reset('importFile');

        if ($importedCount > 0) {
            session()->flash('success', "Importation réussie : {$importedCount} élève(s) mis à jour dans la liste d'appel. N'oubliez pas d'enregistrer.");
        } else {
            session()->flash('error', "Aucun élève correspondant trouvé dans le fichier importé.");
        }
    }

    /**
     * Load WhatsApp API Settings from settings table.
     */
    public function loadWhatsAppSettings()
    {
        $this->whatsapp_api_enabled = Setting::get('whatsapp_api_enabled', 'false') === 'true';
        $this->whatsapp_api_url = Setting::get('whatsapp_api_url', 'https://api.evolution.com/message/sendText/instance_name');
        $this->whatsapp_api_token = Setting::get('whatsapp_api_token', '');
        
        $defaultTemplate = "Bonjour {parent_name}, nous vous informons que votre enfant {student_name} de la classe {class_name} a été marqué(e) {status} ce {date}. Motif: {reason}. Merci de nous contacter pour plus d'informations.";
        $this->whatsapp_message_template = Setting::get('whatsapp_message_template', $defaultTemplate);
    }

    /**
     * Save WhatsApp API Settings to settings table.
     */
    public function saveWhatsAppSettings()
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('Super Admin'), 403, 'Accès non autorisé.');
        $this->validate();

        Setting::set('whatsapp_api_enabled', $this->whatsapp_api_enabled ? 'true' : 'false');
        Setting::set('whatsapp_api_url', $this->whatsapp_api_url);
        Setting::set('whatsapp_api_token', $this->whatsapp_api_token);
        Setting::set('whatsapp_message_template', $this->whatsapp_message_template);

        session()->flash('success', "Configuration WhatsApp mise à jour avec succès.");
    }

    /**
     * Load simulated WhatsApp logs.
     */
    public function loadWhatsAppLogs()
    {
        abort_unless($this->is_head_teacher, 403, 'Accès non autorisé.');
        $this->whatsapp_simulation_logs = json_decode(Setting::get('whatsapp_simulation_logs', '[]'), true) ?? [];
    }

    /**
     * Clear all simulated WhatsApp logs.
     */
    public function clearWhatsAppLogs()
    {
        abort_unless(Auth::user() && Auth::user()->hasRole('Super Admin'), 403, 'Accès non autorisé.');
        Setting::set('whatsapp_simulation_logs', '[]');
        $this->loadWhatsAppLogs();
        session()->flash('success', "Historique des alertes simulées vidé avec succès.");
    }

    /**
     * Load daily attendance grid data formatted as a monthly calendar matrix.
     */
    public function loadMonthlyGridData()
    {
        if (!$this->selectedClassId || !$this->selectedDate) {
            $this->monthlyGridData = [];
            $this->monthDaysList = [];
            return;
        }

        $carbonMonth = Carbon::parse($this->selectedDate)->startOfMonth();
        $this->daysInSelectedMonth = $carbonMonth->daysInMonth;
        
        $this->monthDaysList = [];
        for ($d = 1; $d <= $this->daysInSelectedMonth; $d++) {
            $date = $carbonMonth->copy()->day($d);
            
            // Format short French day names
            $dayName = $date->format('D'); // Mon, Tue...
            $dayNameFr = match($dayName) {
                'Mon' => 'Lu',
                'Tue' => 'Ma',
                'Wed' => 'Me',
                'Thu' => 'Je',
                'Fri' => 'Ve',
                'Sat' => 'Sa',
                'Sun' => 'Di',
                default => $dayName
            };

            $this->monthDaysList[$d] = [
                'date_string' => $date->toDateString(),
                'day_num' => $d,
                'day_name' => $dayNameFr,
                'is_weekend' => $date->isWeekend()
            ];
        }

        // Fetch students
        $studentsQuery = Student::where('school_class_id', $this->selectedClassId)
            ->where('status', 'Active');

        if (!empty($this->monthlySearch)) {
            $q = '%' . $this->monthlySearch . '%';
            $studentsQuery->where(function($query) use ($q) {
                $query->where('first_name', 'like', $q)
                      ->orWhere('last_name', 'like', $q)
                      ->orWhere('matricule', 'like', $q);
            });
        }

        $students = $studentsQuery->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Fetch attendances for these students in this month
        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [
                $carbonMonth->copy()->startOfMonth()->toDateString(),
                $carbonMonth->copy()->endOfMonth()->toDateString()
            ])
            ->get()
            ->groupBy('student_id');

        $this->monthlyGridData = [];
        foreach ($students as $student) {
            $studentAtts = $attendances->get($student->id, collect())->keyBy(function($item) {
                return Carbon::parse($item->date)->day;
            });

            $row = [
                'name' => "{$student->first_name} {$student->last_name}",
                'matricule' => $student->matricule,
                'days' => []
            ];

            for ($d = 1; $d <= $this->daysInSelectedMonth; $d++) {
                $att = $studentAtts->get($d);
                $isWeekend = $this->monthDaysList[$d]['is_weekend'];
                
                $row['days'][$d] = [
                    'status' => $att ? $att->status : null,
                    'is_justified' => $att ? (bool)$att->is_justified : false,
                    'is_weekend' => $isWeekend
                ];
            }

            $this->monthlyGridData[$student->id] = $row;
        }
    }

    /**
     * Calculate all weekly, monthly, and yearly statistics.
     */
    public function calculateStats()
    {
        if (!$this->selectedClassId) {
            $this->attendanceRateWeek = 0;
            $this->attendanceRateMonth = 0;
            $this->attendanceRateYear = 0;
            $this->totalPresentYear = 0;
            $this->totalAbsentYear = 0;
            $this->totalLateYear = 0;
            $this->totalJustifiedYear = 0;
            $this->weeklyTrendData = [];
            $this->classComparisonData = [];
            $this->studentReportData = [];
            $this->filteredStudentReport = [];
            return;
        }

        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            $selectedYearId = AcademicYear::where('is_active', true)->first()?->id 
                ?? AcademicYear::first()?->id;
        }

        // Carbon date scopes
        $today = Carbon::now();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // 1. Calculate General Aggregated Stats (Yearly for the selected class)
        if ($this->selectedClassId) {
            $baseQuery = Attendance::where('school_class_id', $this->selectedClassId);
            if ($selectedYearId) {
                $baseQuery->where('academic_year_id', $selectedYearId);
            }

            // Year stats
            $yearStats = (clone $baseQuery)->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $this->totalPresentYear = $yearStats['present'] ?? 0;
            $this->totalAbsentYear = $yearStats['absent'] ?? 0;
            $this->totalLateYear = $yearStats['late'] ?? 0;
            $this->totalJustifiedYear = (clone $baseQuery)->where('status', 'absent')->where('is_justified', true)->count();

            // Attendance rate calculation
            $totalYear = $this->totalPresentYear + $this->totalAbsentYear + $this->totalLateYear;
            $this->attendanceRateYear = $totalYear > 0 
                ? round((($this->totalPresentYear + $this->totalLateYear) / $totalYear) * 100, 1) 
                : 100;

            // Monthly stats
            $monthStats = (clone $baseQuery)->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalMonth = ($monthStats['present'] ?? 0) + ($monthStats['absent'] ?? 0) + ($monthStats['late'] ?? 0);
            $this->attendanceRateMonth = $totalMonth > 0
                ? round(((($monthStats['present'] ?? 0) + ($monthStats['late'] ?? 0)) / $totalMonth) * 100, 1)
                : 100;

            // Weekly stats
            $weekStats = (clone $baseQuery)->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $totalWeek = ($weekStats['present'] ?? 0) + ($weekStats['absent'] ?? 0) + ($weekStats['late'] ?? 0);
            $this->attendanceRateWeek = $totalWeek > 0
                ? round(((($weekStats['present'] ?? 0) + ($weekStats['late'] ?? 0)) / $totalWeek) * 100, 1)
                : 100;

            // 2. Load Student detailed reporting (weekly / monthly / yearly counts of absences & lates)
            $students = Student::where('school_class_id', $this->selectedClassId)
                ->where('status', 'Active')
                ->orderBy('last_name')
                ->get();

            // Load all attendances for these students for the current academic year
            $allAtt = Attendance::whereIn('student_id', $students->pluck('id'))
                ->when($selectedYearId, function($q) use ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                })
                ->get()
                ->groupBy('student_id');

            $this->studentReportData = [];
            foreach ($students as $student) {
                $studentAtts = $allAtt->get($student->id, collect());
                
                // Perform filtering in PHP to avoid hitting DB multiple times
                $yearPresent = $studentAtts->where('status', 'present')->count();
                $yearAbsent = $studentAtts->where('status', 'absent')->count();
                $yearLate = $studentAtts->where('status', 'late')->count();
                $yearJustified = $studentAtts->where('status', 'absent')->where('is_justified', true)->count();

                // Month stats
                $monthAtts = $studentAtts->filter(function($item) use ($startOfMonth, $endOfMonth) {
                    $d = Carbon::parse($item->date);
                    return $d->between($startOfMonth, $endOfMonth);
                });
                $monthPresent = $monthAtts->where('status', 'present')->count();
                $monthAbsent = $monthAtts->where('status', 'absent')->count();
                $monthLate = $monthAtts->where('status', 'late')->count();

                $totalDays = $yearPresent + $yearAbsent + $yearLate;
                $rate = $totalDays > 0 ? round((($yearPresent + $yearLate) / $totalDays) * 100, 1) : 100;

                $this->studentReportData[$student->id] = [
                    'name' => "{$student->first_name} {$student->last_name}",
                    'matricule' => $student->matricule,
                    'year_present' => $yearPresent,
                    'year_absent' => $yearAbsent,
                    'year_late' => $yearLate,
                    'year_justified' => $yearJustified,
                    'month_present' => $monthPresent,
                    'month_absent' => $monthAbsent,
                    'month_late' => $monthLate,
                    'rate' => $rate,
                ];
            }
        }

        // 3. Weekly Trend Data (Past 5 weeks)
        $this->weeklyTrendData = ['dates' => [], 'rates' => []];
        for ($i = 4; $i >= 0; $i--) {
            $wStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $wEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $query = Attendance::whereBetween('date', [$wStart->toDateString(), $wEnd->toDateString()]);
            if ($this->selectedClassId) {
                $query->where('school_class_id', $this->selectedClassId);
            }
            if ($selectedYearId) {
                $query->where('academic_year_id', $selectedYearId);
            }

            $stats = $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = ($stats['present'] ?? 0) + ($stats['absent'] ?? 0) + ($stats['late'] ?? 0);
            $rate = $total > 0 ? round(((($stats['present'] ?? 0) + ($stats['late'] ?? 0)) / $total) * 100, 1) : 100;

            $this->weeklyTrendData['dates'][] = "Sem. -" . $i;
            $this->weeklyTrendData['rates'][] = $rate;
        }

        // 4. Class comparison data
        $this->classComparisonData = ['classes' => [], 'rates' => []];
        $allClasses = SchoolClass::where('status', 'Active')->get();
        foreach ($allClasses as $c) {
            $query = Attendance::where('school_class_id', $c->id);
            if ($selectedYearId) {
                $query->where('academic_year_id', $selectedYearId);
            }

            $stats = $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = ($stats['present'] ?? 0) + ($stats['absent'] ?? 0) + ($stats['late'] ?? 0);
            $rate = $total > 0 ? round(((($stats['present'] ?? 0) + ($stats['late'] ?? 0)) / $total) * 100, 1) : 100;

            $this->classComparisonData['classes'][] = $c->name;
            $this->classComparisonData['rates'][] = $rate;
        }

        // 5. Filter student report
        $this->filteredStudentReport = $this->studentReportData;
        if ($this->reportSearch) {
            $this->filteredStudentReport = array_filter($this->studentReportData, function($row) {
                return str_contains(strtolower($row['name']), strtolower($this->reportSearch))
                    || str_contains(strtolower($row['matricule']), strtolower($this->reportSearch));
            });
        }
    }

    public function updatedReportSearch()
    {
        $this->calculateStats();
    }
}
