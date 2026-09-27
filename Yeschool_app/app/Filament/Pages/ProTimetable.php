<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Schedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ProTimetable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-timetable';
    protected static ?string $title = 'Emploi du temps';

    public $schoolClasses = [];
    public $subjects = [];
    public $selectedClassId = null;
    
    // The weekly schedule structure
    public $weeklySchedule = [];
    public $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    public $timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

    // Gantt / Calendar State
    public $viewMode = 'weekly_grid'; // 'weekly_grid' or 'gantt'
    public $ganttType = 'classes'; // 'classes' or 'teachers'
    public $selectedMonth;
    public $selectedWeek;
    public $selectedDayIndex = 0;
    public $allSchedules = [];

    // Offcanvas / Form
    public $isOffcanvasOpen = false;
    public $schedule_id;
    public $form_subject_id;
    public $form_day;
    public $form_start_time;
    public $form_end_time;
    public $form_teacher_id;
    public $form_room;

    public $teachers = [];

    public function mount()
    {
        $this->schoolClasses = SchoolClass::orderBy('level')->orderBy('name')->get()->toArray();
        $this->subjects = Subject::orderBy('name')->get()->toArray();
        $this->teachers = \App\Models\Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();

        $this->selectedMonth = date('Y-m'); // e.g. "2026-07"
        $weeks = $this->getWeeksOfMonth();
        if (!empty($weeks)) {
            $this->selectedWeek = $weeks[0]['week_num'];
        } else {
            $this->selectedWeek = (int)date('W');
        }

        if (!empty($this->schoolClasses)) {
            $this->selectedClassId = $this->schoolClasses[0]['id'];
            $this->loadSchedule();
        }
    }

    public function updatedSelectedClassId()
    {
        $this->loadSchedule();
    }

    public function updatedViewMode()
    {
        $this->loadSchedule();
    }

    public function updatedGanttType()
    {
        $this->loadSchedule();
    }

    public function updatedSelectedMonth()
    {
        $weeks = $this->getWeeksOfMonth();
        if (!empty($weeks)) {
            $this->selectedWeek = $weeks[0]['week_num'];
        }
        $this->loadSchedule();
    }

    public function updatedSelectedWeek()
    {
        $this->loadSchedule();
    }

    public function getWeeksOfMonth()
    {
        if (!$this->selectedMonth) {
            $this->selectedMonth = date('Y-m');
        }
        
        $year = (int)substr($this->selectedMonth, 0, 4);
        $month = (int)substr($this->selectedMonth, 5, 2);
        
        $firstDay = strtotime("$year-$month-01");
        $lastDay = strtotime("last day of $year-$month");
        
        $startWeek = (int)date('W', $firstDay);
        $endWeek = (int)date('W', $lastDay);
        
        // Handle year boundary
        if ($endWeek < $startWeek) {
            $endWeek = 53;
        }
        
        $weeks = [];
        for ($w = $startWeek; $w <= $endWeek; $w++) {
            // Get dates for this week
            $dto = new \DateTime();
            $dto->setISODate($year, $w);
            $monday = $dto->format('d M');
            $dto->modify('+5 days');
            $saturday = $dto->format('d M');
            
            $weeks[] = [
                'week_num' => $w,
                'label' => "Semaine $w ($monday - $saturday)",
                'year' => $year
            ];
        }
        
        return $weeks;
    }

    public function getWeekDates()
    {
        $year = (int)substr($this->selectedMonth, 0, 4);
        $dto = new \DateTime();
        $dto->setISODate($year, (int)$this->selectedWeek);
        
        $dates = [];
        $dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        
        for ($i = 0; $i < 6; $i++) {
            $dates[] = [
                'day_name' => $dayNames[$i],
                'date_str' => $dto->format('d M Y'),
                'short_date' => $dto->format('d M')
            ];
            $dto->modify('+1 day');
        }
        
        return $dates;
    }

    public function loadSchedule()
    {
        if ($this->viewMode === 'gantt') {
            $this->allSchedules = Schedule::with(['subject', 'teacher', 'schoolClass'])->get()->toArray();
            return;
        }

        if (!$this->selectedClassId) return;

        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('school_class_id', $this->selectedClassId)
            ->get();

        // Organize by day
        $structured = [];
        foreach ($this->days as $day) {
            $structured[$day] = $schedules->where('day_of_week', $day)->sortBy('start_time')->toArray();
        }

        $this->weeklySchedule = $structured;
    }

    public function createSchedule($day = null, $time = null)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $this->reset(['schedule_id', 'form_subject_id', 'form_teacher_id', 'form_room']);
        
        $this->form_day = $day ?? 'Lundi';
        
        if ($time) {
            $this->form_start_time = $time;
            $this->form_end_time = date('H:i', strtotime($time) + 7200);
        } else {
            $this->form_start_time = '08:00';
            $this->form_end_time = '10:00';
        }
        
        $this->isOffcanvasOpen = true;
    }
 
    public function createScheduleFromGantt($rowId, $time)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $dayName = $this->days[$this->selectedDayIndex];
        
        $this->resetValidation();
        $this->reset(['schedule_id', 'form_subject_id', 'form_teacher_id', 'form_room']);
        
        $this->form_day = $dayName;
        $this->form_start_time = $time;
        $this->form_end_time = date('H:i', strtotime($time) + 7200); // 2 hours duration
        
        if ($this->ganttType === 'classes') {
            $this->selectedClassId = $rowId;
        } else {
            $this->form_teacher_id = $rowId;
            if (!$this->selectedClassId && !empty($this->schoolClasses)) {
                $this->selectedClassId = $this->schoolClasses[0]['id'];
            }
        }
        
        $this->isOffcanvasOpen = true;
    }
 
    public function editSchedule($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $schedule = Schedule::findOrFail($id);
        
        $this->schedule_id = $schedule->id;
        $this->form_subject_id = $schedule->subject_id;
        $this->form_day = $schedule->day_of_week;
        $this->form_start_time = substr($schedule->start_time, 0, 5); // get HH:mm
        $this->form_end_time = substr($schedule->end_time, 0, 5);
        $this->form_teacher_id = $schedule->teacher_id;
        $this->form_room = $schedule->room;
 
        $this->isOffcanvasOpen = true;
    }
 
    public function saveSchedule()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->validate([
            'form_subject_id' => 'required|exists:subjects,id',
            'form_day' => 'required|string',
            'form_start_time' => 'required',
            'form_end_time' => 'required|after:form_start_time',
            'form_teacher_id' => 'nullable|exists:teachers,id',
            'form_room' => 'nullable|string',
        ]);
 
        Schedule::updateOrCreate(
            ['id' => $this->schedule_id],
            [
                'school_class_id' => $this->selectedClassId,
                'subject_id' => $this->form_subject_id,
                'day_of_week' => $this->form_day,
                'start_time' => $this->form_start_time,
                'end_time' => $this->form_end_time,
                'teacher_id' => $this->form_teacher_id,
                'room' => $this->form_room,
            ]
        );
 
        $this->isOffcanvasOpen = false;
        $this->loadSchedule();
    }
 
    public function deleteSchedule($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        Schedule::findOrFail($id)->delete();
        $this->loadSchedule();
    }

    public function exportPdf()
    {
        if (!$this->selectedClassId && $this->viewMode === 'weekly_grid') {
            return;
        }

        $schoolClass = SchoolClass::find($this->selectedClassId);
        
        // Fetch School Information
        $school = Auth::user()->school ?? \App\Models\School::first();
        $schoolName = $school ? $school->name : 'Yeschool Établissement';
        $schoolEmail = $school ? $school->email : 'contact@yeschool.com';
        $schoolPhone = $school ? $school->phone : '+243 000 000 000';
        $schoolAddress = $school ? $school->address : 'Adresse non spécifiée';
        
        // Encode Logo to Base64 (DomPDF compatibility)
        $logoBase64 = null;
        if ($school && $school->logo) {
            $logoPath = public_path('storage/' . $school->logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . $logoData;
            }
        }

        // Unique 10-character code
        $uniqueCode = strtoupper(substr(md5(time() . rand()), 0, 10));

        // Digital signature QR data
        $qrData = "Yeschool EDT Signature\nCode: $uniqueCode\nEtablissement: $schoolName\nEdition: " . date('d/m/Y H:i') . "\nCertifie Conforme.";

        // Fetch and base64-encode QR code to avoid DomPDF remote resolution issues
        $qrCodeBase64 = null;
        try {
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData);
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
                "http" => [
                    "timeout" => 3, // 3 seconds timeout
                ]
            ]);
            $qrImageContents = @file_get_contents($qrUrl, false, $context);
            if ($qrImageContents) {
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrImageContents);
            }
        } catch (\Exception $e) {
            // Graceful fallback
        }

        if ($this->viewMode === 'gantt') {
            $activeDayName = $this->days[$this->selectedDayIndex];
            $schedules = Schedule::with(['subject', 'teacher', 'schoolClass'])
                ->where('day_of_week', $activeDayName)
                ->get()
                ->toArray();
            
            $dates = $this->getWeekDates();
            $activeDate = $dates[$this->selectedDayIndex]['date_str'];
            
            $data = [
                'viewMode' => 'gantt',
                'ganttType' => $this->ganttType,
                'schedules' => $schedules,
                'activeDayName' => $activeDayName,
                'activeDate' => $activeDate,
                'schoolClasses' => $this->schoolClasses,
                'teachers' => $this->teachers,
                'title' => "Emploi du temps - Gantt ($activeDayName $activeDate)",
                'footer' => "Généré par Yeschool - " . date('d/m/Y H:i'),
                'schoolName' => $schoolName,
                'schoolEmail' => $schoolEmail,
                'schoolPhone' => $schoolPhone,
                'schoolAddress' => $schoolAddress,
                'logoBase64' => $logoBase64,
                'uniqueCode' => $uniqueCode,
                'qrCodeBase64' => $qrCodeBase64,
                'qrData' => $qrData
            ];
        } else {
            $schedules = Schedule::with(['subject', 'teacher'])
                ->where('school_class_id', $this->selectedClassId)
                ->get();
                
            $structured = [];
            foreach ($this->days as $day) {
                $structured[$day] = $schedules->where('day_of_week', $day)->sortBy('start_time')->toArray();
            }

            $data = [
                'viewMode' => 'weekly_grid',
                'className' => $schoolClass ? $schoolClass->name . ' (' . $schoolClass->level . ')' : 'Classe Inconnue',
                'schedules' => $structured,
                'days' => $this->days,
                'timeSlots' => $this->timeSlots,
                'title' => "Emploi du Temps - Classe " . ($schoolClass ? $schoolClass->name : ''),
                'footer' => "Généré par Yeschool - " . date('d/m/Y H:i'),
                'schoolName' => $schoolName,
                'schoolEmail' => $schoolEmail,
                'schoolPhone' => $schoolPhone,
                'schoolAddress' => $schoolAddress,
                'logoBase64' => $logoBase64,
                'uniqueCode' => $uniqueCode,
                'qrCodeBase64' => $qrCodeBase64,
                'qrData' => $qrData
            ];
        }

        $pdf = Pdf::loadView('pdf.timetable', $data)->setPaper('a4', 'landscape');
        
        // Configure options directly on Dompdf
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'emploi_du_temps_' . $uniqueCode . '.pdf');
    }
}
