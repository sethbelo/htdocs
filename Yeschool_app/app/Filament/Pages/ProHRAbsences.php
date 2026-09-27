<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\Timesheet;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ProHRAbsences extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-absences';
    protected static ?string $title = 'Gestion des Absences';

    // Disable default navigation registration, we link it manually in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $timesheetsList = [];
    public $personnelList = [];
    public $filter_ts_month;

    // Attendance PDF Export State
    public $isExportModalOpen = false;
    public $export_teacher_id = '';
    public $export_start_date;
    public $export_end_date;
    public $export_include_absences = true;
    public $export_only_absences = false;

    // Timesheet Form Fields
    public $isTimesheetOffcanvasOpen = false;
    public $timesheet_id;
    public $form_ts_teacher_id;
    public $form_ts_date;
    public $form_ts_check_in;
    public $form_ts_check_out;
    public $form_ts_break_minutes = 60;
    public $form_ts_overtime_hours = 0;
    public $form_ts_notes;

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;
    public $confirmDeleteAction;
    public $confirmDeleteMessage;

    public function promptDelete($id, $action, $message = 'Êtes-vous sûr de vouloir supprimer cet élément ?')
    {
        $this->deletingId = $id;
        $this->confirmDeleteAction = $action;
        $this->confirmDeleteMessage = $message;
        $this->isConfirmDeleteModalOpen = true;
    }

    public function confirmDelete()
    {
        if (!$this->deletingId || !$this->confirmDeleteAction) return;

        $action = $this->confirmDeleteAction;
        $id = $this->deletingId;

        $this->isConfirmDeleteModalOpen = false;
        $this->deletingId = null;
        $this->confirmDeleteAction = null;

        if (method_exists($this, $action)) {
            $this->$action($id);
        }
    }

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        $this->filter_ts_month = date('Y-m');
        $this->export_start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->export_end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $query = Timesheet::with('teacher');
        if ($this->filter_ts_month) {
            $query->where('date', 'like', $this->filter_ts_month . '%');
        }
        $this->timesheetsList = $query->orderByDesc('date')->get()->toArray();
        $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
    }

    public function updatedFilterTsMonth()
    {
        $this->loadData();
    }

    public function createTimesheet()
    {
        $this->resetValidation();
        $this->reset(['timesheet_id', 'form_ts_teacher_id', 'form_ts_date', 'form_ts_check_in', 'form_ts_check_out', 'form_ts_break_minutes', 'form_ts_overtime_hours', 'form_ts_notes']);
        $this->form_ts_date = date('Y-m-d');
        $this->form_ts_check_in = '08:00';
        $this->form_ts_check_out = '17:00';
        $this->form_ts_break_minutes = 60;
        $this->form_ts_overtime_hours = 0;
        $this->isTimesheetOffcanvasOpen = true;
    }

    public function editTimesheet($id)
    {
        $this->resetValidation();
        $t = Timesheet::findOrFail($id);
        $this->timesheet_id = $t->id;
        $this->form_ts_teacher_id = $t->teacher_id;
        $this->form_ts_date = $t->date->format('Y-m-d');
        $this->form_ts_check_in = substr($t->check_in, 0, 5);
        $this->form_ts_check_out = $t->check_out ? substr($t->check_out, 0, 5) : '';
        $this->form_ts_break_minutes = $t->break_minutes;
        $this->form_ts_overtime_hours = $t->overtime_hours;
        $this->form_ts_notes = $t->notes;
        $this->isTimesheetOffcanvasOpen = true;
    }

    public function saveTimesheet()
    {
        $this->validate([
            'form_ts_teacher_id' => 'required|exists:teachers,id',
            'form_ts_date' => 'required|date',
            'form_ts_check_in' => 'required',
        ]);

        $totalHours = 0;
        if ($this->form_ts_check_in && $this->form_ts_check_out) {
            $in = Carbon::parse($this->form_ts_check_in);
            $out = Carbon::parse($this->form_ts_check_out);
            $diffMinutes = $out->diffInMinutes($in);
            $totalHours = ($diffMinutes - intval($this->form_ts_break_minutes)) / 60;
            if ($totalHours < 0) $totalHours = 0;
        }

        Timesheet::updateOrCreate(
            ['id' => $this->timesheet_id],
            [
                'teacher_id' => $this->form_ts_teacher_id,
                'date' => $this->form_ts_date,
                'check_in' => $this->form_ts_check_in,
                'check_out' => $this->form_ts_check_out,
                'break_minutes' => $this->form_ts_break_minutes ?: 0,
                'overtime_hours' => $this->form_ts_overtime_hours ?: 0,
                'total_hours' => $totalHours,
                'notes' => $this->form_ts_notes,
            ]
        );

        $this->isTimesheetOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Fiche de temps enregistrée.');
    }

    public function deleteTimesheet($id)
    {
        Timesheet::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('success', 'Fiche de temps supprimée.');
    }

    public function openExportModal()
    {
        $this->export_teacher_id = '';
        $this->export_start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->export_end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->export_include_absences = true;
        $this->export_only_absences = false;
        $this->isExportModalOpen = true;
    }

    public function exportAttendancePdf()
    {
        $this->validate([
            'export_start_date' => 'required|date',
            'export_end_date' => 'required|date|after_or_equal:export_start_date',
        ]);

        $start = Carbon::parse($this->export_start_date);
        $end = Carbon::parse($this->export_end_date);
        $dates = [];
        $temp = $start->copy();
        while ($temp->lte($end)) {
            $dates[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        $teachersQuery = Teacher::query();
        if ($this->export_teacher_id) {
            $teachersQuery->where('id', $this->export_teacher_id);
        } else {
            $teachersQuery->where('status', 'Actif');
        }
        $teachers = $teachersQuery->orderBy('last_name')->get();

        $timesheets = Timesheet::whereBetween('date', [$this->export_start_date, $this->export_end_date])->get()->groupBy('teacher_id');

        $leaves = LeaveRequest::where('status', 'Approuvé')
            ->where(function ($q) {
                $q->whereBetween('start_date', [$this->export_start_date, $this->export_end_date])
                  ->orWhereBetween('end_date', [$this->export_start_date, $this->export_end_date])
                  ->orWhere(function ($sub) {
                      $sub->where('start_date', '<=', $this->export_start_date)
                          ->where('end_date', '>=', $this->export_end_date);
                  });
            })
            ->get();

        $records = [];
        $stats = [
            'present' => 0,
            'absent_justified' => 0,
            'absent_unjustified' => 0,
            'total_hours' => 0.0,
        ];

        foreach ($teachers as $teacher) {
            $tTimesheets = $timesheets->get($teacher->id, collect());
            
            foreach ($dates as $dateStr) {
                $dayCarbon = Carbon::parse($dateStr);
                $isWeekend = $dayCarbon->isWeekend();

                $ts = $tTimesheets->first(fn($t) => $t->date->format('Y-m-d') === $dateStr);

                if ($ts) {
                    if ($this->export_only_absences) {
                        continue;
                    }
                    $records[] = [
                        'date' => $dayCarbon->locale('fr')->isoFormat('dddd DD MMM YYYY'),
                        'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                        'status' => 'Présent',
                        'check_in' => $ts->check_in,
                        'check_out' => $ts->check_out,
                        'hours' => $ts->total_hours,
                        'notes' => $ts->notes ?: '-',
                    ];
                    $stats['present']++;
                    $stats['total_hours'] += floatval($ts->total_hours);
                } else {
                    $leave = $leaves->first(function ($l) use ($teacher, $dayCarbon) {
                        return $l->teacher_id === $teacher->id && 
                            $dayCarbon->between(Carbon::parse($l->start_date), Carbon::parse($l->end_date));
                    });

                    if ($leave) {
                        if (!$this->export_include_absences) {
                            continue;
                        }
                        $records[] = [
                            'date' => $dayCarbon->locale('fr')->isoFormat('dddd DD MMM YYYY'),
                            'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'status' => 'Absent (' . $leave->type . ')',
                            'check_in' => '-',
                            'check_out' => '-',
                            'hours' => 0,
                            'notes' => $leave->reason,
                        ];
                        $stats['absent_justified']++;
                    } else {
                        if ($isWeekend) {
                            continue;
                        }
                        if (!$this->export_include_absences) {
                            continue;
                        }
                        $records[] = [
                            'date' => $dayCarbon->locale('fr')->isoFormat('dddd DD MMM YYYY'),
                            'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                            'status' => 'Absent (Non justifié)',
                            'check_in' => '-',
                            'check_out' => '-',
                            'hours' => 0,
                            'notes' => 'Aucune présence ou absence enregistrée.',
                        ];
                        $stats['absent_unjustified']++;
                    }
                }
            }
        }

        $this->isExportModalOpen = false;

        $pdfData = [
            'records' => $records,
            'stats' => $stats,
            'period' => $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y'),
            'teacher_name' => $this->export_teacher_id ? ($teachers->first()->first_name . ' ' . $teachers->first()->last_name) : 'Tous les collaborateurs',
        ];

        return response()->streamDownload(function () use ($pdfData) {
            echo Pdf::loadView('pdf.attendance', $pdfData)->output();
        }, 'fiche_presence_' . now()->format('Ymd_His') . '.pdf');
    }
}
