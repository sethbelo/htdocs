<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use App\Models\Timesheet;
use App\Models\Payslip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Barryvdh\DomPDF\Facade\Pdf;

class ProHR extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr';
    protected static ?string $title = 'Ressources Humaines';

    public $activeTab = 'personnel'; // 'personnel', 'conges', 'timesheets', 'paie'

    // Data lists
    public $personnelList = [];
    public $leaveRequests = [];
    public $timesheetsList = [];
    public $payslipsList = [];

    // Filters
    public $filter_personnel_status = 'all';
    public $filter_leave_status = 'all';
    public $filter_ts_month;

    // Personnel Form
    public $isPersonnelOffcanvasOpen = false;
    public $personnel_id;
    public $form_first_name;
    public $form_last_name;
    public $form_email;
    public $form_phone;
    public $form_specialty;
    public $form_salary;
    public $form_status = 'Candidat';
    public $form_contract_signed_at;
    public $form_contract_ended_at;
    public $form_recruitment_notes;
    public $form_date_of_birth;
    public $avatar_upload;
    public $avatar_webcam;
    public $avatar_path;
    public $cv_upload;
    public $cv_path;
    public $cover_letter_upload;
    public $cover_letter_path;

    // Leave Request Form
    public $isLeaveOffcanvasOpen = false;
    public $leave_id;
    public $form_teacher_id;
    public $form_start_date;
    public $form_end_date;
    public $form_reason;
    public $form_leave_status = 'En attente';
    public $form_leave_type = 'Congé payé légal';
    public $form_interim_teacher_id;

    // Interactive Alerts and Previews
    public $estimated_days = 0;
    public $available_balance = 0;
    public $post_balance = 0;
    public $overlap_warning = '';
    public $interim_warning = '';
    public $type_info_message = '';

    // Timesheet Form
    public $isTimesheetOffcanvasOpen = false;
    public $timesheet_id;
    public $form_ts_teacher_id;
    public $form_ts_date;
    public $form_ts_check_in;
    public $form_ts_check_out;
    public $form_ts_break_minutes = 60;
    public $form_ts_overtime_hours = 0;
    public $form_ts_notes;

    // Payslip Form
    public $isPayslipOffcanvasOpen = false;
    public $payslip_id;
    public $form_ps_teacher_id;
    public $form_ps_month_year;
    public $form_ps_base_salary;
    public $form_ps_bonus = 0.00;
    public $form_ps_deductions = 0.00;
    public $form_ps_status = 'Brouillon';
    public $form_ps_payment_date;
    public $form_ps_payment_method = 'Virement';
    public $form_ps_notes;

    // Payslip preview overlay
    public $viewing_payslip_id = null;

    // Detailed Payroll Report State
    public $filter_teacher_id;
    public $payroll_start_date;
    public $payroll_end_date;
    public $isDetailedPayrollModalOpen = false;

    // Attendance PDF Export State
    public $isExportModalOpen = false;
    public $export_teacher_id = '';
    public $export_start_date;
    public $export_end_date;
    public $export_include_absences = true;
    public $export_only_absences = false;

    // Assistant Paie Simulation
    public $simulated_teacher_id;
    public $simulated_bonus = 0.00;
    public $simulated_deductions = 0.00;
    public $simulated_net = 0.00;

    public function mount()
    {
        abort_unless(auth()->user() && auth()->user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé. Réservé aux ressources humaines.');

        $this->filter_ts_month = date('Y-m');
        $this->export_start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->export_end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        $tab = request()->query('tab');
        if (in_array($tab, ['personnel', 'conges', 'timesheets', 'paie'])) {
            $this->activeTab = $tab;
        }

        $this->loadData();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->loadData();
    }

    public function updatedFilterPersonnelStatus() { $this->loadData(); }
    public function updatedFilterLeaveStatus() { $this->loadData(); }
    public function updatedFilterTsMonth() { $this->loadData(); }

    public function loadData()
    {
        if ($this->activeTab === 'personnel') {
            $query = Teacher::query();
            if ($this->filter_personnel_status !== 'all') {
                $query->where('status', $this->filter_personnel_status);
            }
            $this->personnelList = $query->orderBy('last_name')->get()->toArray();
        } elseif ($this->activeTab === 'conges') {
            $query = LeaveRequest::with('teacher');
            if ($this->filter_leave_status !== 'all') {
                $query->where('status', $this->filter_leave_status);
            }
            $this->leaveRequests = $query->orderByDesc('created_at')->get()->toArray();
            $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
        } elseif ($this->activeTab === 'timesheets') {
            $query = Timesheet::with('teacher');
            if ($this->filter_ts_month) {
                $query->where('date', 'like', $this->filter_ts_month . '%');
            }
            $this->timesheetsList = $query->orderByDesc('date')->get()->toArray();
            $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
        } elseif ($this->activeTab === 'paie') {
            $this->payslipsList = Payslip::with('teacher')->orderByDesc('created_at')->get()->toArray();
            $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
            
            if (!$this->simulated_teacher_id && !empty($this->personnelList)) {
                $this->simulated_teacher_id = $this->personnelList[0]['id'];
            }
            $this->calculateSimulation();
        }
    }

    public function updatedSimulatedTeacherId()
    {
        $this->calculateSimulation();
    }

    public function updatedSimulatedBonus()
    {
        $this->calculateSimulation();
    }

    public function updatedSimulatedDeductions()
    {
        $this->calculateSimulation();
    }

    public function calculateSimulation()
    {
        if ($this->simulated_teacher_id) {
            $teacher = Teacher::find($this->simulated_teacher_id);
            if ($teacher) {
                $base = $teacher->salary ?: 0;
                $bonus = floatval($this->simulated_bonus) ?: 0;
                $deductions = floatval($this->simulated_deductions) ?: 0;
                $this->simulated_net = $base + $bonus - $deductions;
            }
        }
    }

    public function createPersonnel()
    {
        $this->resetValidation();
        $this->reset(['personnel_id', 'form_first_name', 'form_last_name', 'form_email', 'form_phone', 'form_specialty', 'form_salary', 'form_status', 'form_contract_signed_at', 'form_contract_ended_at', 'form_recruitment_notes', 'form_date_of_birth', 'avatar_upload', 'avatar_webcam', 'avatar_path', 'cv_upload', 'cv_path', 'cover_letter_upload', 'cover_letter_path']);
        $this->form_status = 'Candidat';
        $this->isPersonnelOffcanvasOpen = true;
    }

    public function editPersonnel($id)
    {
        $this->resetValidation();
        $p = Teacher::findOrFail($id);
        
        $this->personnel_id = $p->id;
        $this->form_first_name = $p->first_name;
        $this->form_last_name = $p->last_name;
        $this->form_email = $p->email;
        $this->form_phone = $p->phone;
        $this->form_specialty = $p->specialty;
        $this->form_salary = $p->salary;
        $this->form_status = $p->status;
        $this->form_contract_signed_at = $p->contract_signed_at ? \Carbon\Carbon::parse($p->contract_signed_at)->format('Y-m-d') : null;
        $this->form_contract_ended_at = $p->contract_ended_at ? \Carbon\Carbon::parse($p->contract_ended_at)->format('Y-m-d') : null;
        $this->form_recruitment_notes = $p->recruitment_notes;
        $this->form_date_of_birth = $p->date_of_birth ? \Carbon\Carbon::parse($p->date_of_birth)->format('Y-m-d') : null;
        $this->avatar_path = $p->avatar;
        $this->avatar_upload = null;
        $this->avatar_webcam = null;
        $this->cv_path = $p->cv_path;
        $this->cv_upload = null;
        $this->cover_letter_path = $p->cover_letter_path;
        $this->cover_letter_upload = null;

        $this->isPersonnelOffcanvasOpen = true;
    }

    public function savePersonnel()
    {
        $this->validate([
            'form_first_name' => 'required|string',
            'form_last_name' => 'required|string',
            'form_email' => 'nullable|email',
            'form_phone' => 'nullable|string',
            'form_specialty' => 'nullable|string',
            'form_salary' => 'nullable|numeric',
            'form_status' => 'required|in:Candidat,Actif,En congé,Ancien',
            'form_contract_signed_at' => 'nullable|date',
            'form_contract_ended_at' => 'nullable|date|after_or_equal:form_contract_signed_at',
            'form_recruitment_notes' => 'nullable|string',
            'form_date_of_birth' => 'nullable|date',
        ]);

        $avatarPath = $this->avatar_path;
        if ($this->avatar_webcam) {
            $imgData = $this->avatar_webcam;
            if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                $imgData = substr($imgData, strpos($imgData, ',') + 1);
                $type = strtolower($type[1]);
                $data = base64_decode($imgData);
                $filename = 'avatar_' . uniqid() . '.' . $type;
                Storage::disk('public')->put('avatars/' . $filename, $data);
                $avatarPath = 'avatars/' . $filename;
            }
        } elseif ($this->avatar_upload) {
            $this->validate([
                'avatar_upload' => 'image|max:1024',
            ]);
            $avatarPath = $this->avatar_upload->store('avatars', 'public');
        }

        $cvPath = $this->cv_path;
        if ($this->cv_upload) {
            $this->validate([
                'cv_upload' => 'file|mimes:pdf|max:2048',
            ]);
            $cvPath = $this->cv_upload->store('dossiers', 'public');
        }

        $coverLetterPath = $this->cover_letter_path;
        if ($this->cover_letter_upload) {
            $this->validate([
                'cover_letter_upload' => 'file|mimes:pdf|max:2048',
            ]);
            $coverLetterPath = $this->cover_letter_upload->store('dossiers', 'public');
        }

        Teacher::updateOrCreate(
            ['id' => $this->personnel_id],
            [
                'first_name' => $this->form_first_name,
                'last_name' => $this->form_last_name,
                'avatar' => $avatarPath,
                'cv_path' => $cvPath,
                'cover_letter_path' => $coverLetterPath,
                'email' => $this->form_email,
                'phone' => $this->form_phone,
                'specialty' => $this->form_specialty,
                'salary' => $this->form_salary,
                'status' => $this->form_status,
                'contract_signed_at' => $this->form_contract_signed_at,
                'contract_ended_at' => $this->form_contract_ended_at,
                'recruitment_notes' => $this->form_recruitment_notes,
                'date_of_birth' => $this->form_date_of_birth,
            ]
        );

        $this->isPersonnelOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Collaborateur enregistré avec succès.');
    }

    public function signContract($id)
    {
        $p = Teacher::findOrFail($id);
        $p->update([
            'status' => 'Actif',
            'contract_signed_at' => now(),
        ]);
        $this->loadData();
        session()->flash('success', 'Contrat signé. Le candidat est maintenant un collaborateur Actif.');
    }

    public function terminateContract($id)
    {
        $p = Teacher::findOrFail($id);
        $p->update([
            'status' => 'Ancien',
            'contract_ended_at' => now(),
        ]);
        $this->loadData();
        session()->flash('success', 'Collaborateur marqué comme départ (Ancien).');
    }

    public function deletePersonnel($id)
    {
        Teacher::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('success', 'Collaborateur supprimé.');
    }

    // --- Leave Requests Actions ---
    public function createLeaveRequest()
    {
        $this->resetValidation();
        $this->reset(['leave_id', 'form_teacher_id', 'form_start_date', 'form_end_date', 'form_reason', 'form_leave_status', 'form_interim_teacher_id']);
        $this->form_leave_status = 'En attente';
        $this->form_leave_type = 'Congé payé légal';
        $this->isLeaveOffcanvasOpen = true;
        $this->runRealtimeValidation();
    }

    public function editLeaveRequest($id)
    {
        $this->resetValidation();
        $l = LeaveRequest::findOrFail($id);
        
        $this->leave_id = $l->id;
        $this->form_teacher_id = $l->teacher_id;
        $this->form_start_date = $l->start_date->format('Y-m-d');
        $this->form_end_date = $l->end_date->format('Y-m-d');
        $this->form_reason = $l->reason;
        $this->form_leave_status = $l->status;
        $this->form_interim_teacher_id = $l->interim_teacher_id;
        $this->form_leave_type = $l->type ?? 'Congé payé légal';

        $this->isLeaveOffcanvasOpen = true;
        $this->runRealtimeValidation();
    }

    public function updatedFormTeacherId() { $this->runRealtimeValidation(); }
    public function updatedFormStartDate() { $this->runRealtimeValidation(); }
    public function updatedFormEndDate() { $this->runRealtimeValidation(); }
    public function updatedFormLeaveType() { $this->runRealtimeValidation(); }
    public function updatedFormInterimTeacherId() { $this->runRealtimeValidation(); }

    public function runRealtimeValidation()
    {
        $this->estimated_days = 0;
        $this->available_balance = 0;
        $this->post_balance = 0;
        $this->overlap_warning = '';
        $this->interim_warning = '';
        $this->type_info_message = '';

        if (!$this->form_teacher_id || !$this->form_start_date || !$this->form_end_date) {
            return;
        }

        try {
            $start = Carbon::parse($this->form_start_date);
            $end = Carbon::parse($this->form_end_date);
        } catch (\Exception $e) {
            return;
        }

        if ($start->gt($end)) {
            return;
        }

        $teacher = Teacher::find($this->form_teacher_id);
        if (!$teacher) return;

        // 1. Calculate duration based on type
        $this->estimated_days = LeaveRequest::calculateDays($this->form_start_date, $this->form_end_date, $this->form_leave_type);

        // 2. Fetch balance info
        $currentYear = date('Y');
        if (in_array($this->form_leave_type, ['RTT', 'Congé payé légal'])) {
            $isRtt = ($this->form_leave_type === 'RTT');
            $allowance = $isRtt ? ($teacher->rtt_allowance ?? 10) : ($teacher->annual_leave_allowance ?? 30);
            
            // Calculate already used (exclude current leave being edited)
            $usedDays = 0;
            $query = LeaveRequest::where('teacher_id', $this->form_teacher_id)
                ->where('type', $this->form_leave_type)
                ->where('status', 'Approuvé')
                ->where(function ($q) use ($currentYear) {
                    $q->whereYear('start_date', $currentYear)
                      ->orWhereYear('end_date', $currentYear);
                });
            if ($this->leave_id) {
                $query->where('id', '!=', $this->leave_id);
            }
            $approvedLeaves = $query->get();

            foreach ($approvedLeaves as $l) {
                $sDate = $l->start_date;
                $eDate = $l->end_date;
                if ($sDate->format('Y') < $currentYear) $sDate = Carbon::create($currentYear, 1, 1);
                if ($eDate->format('Y') > $currentYear) $eDate = Carbon::create($currentYear, 12, 31);
                $usedDays += LeaveRequest::calculateDays($sDate->format('Y-m-d'), $eDate->format('Y-m-d'), $this->form_leave_type);
            }

            $this->available_balance = max(0, $allowance - $usedDays);
            $this->post_balance = max(0, $this->available_balance - $this->estimated_days);

            // Trigger warning if balance is exceeded
            if ($this->estimated_days > $this->available_balance) {
                $this->overlap_warning = "Solde insuffisant pour ce type de congé (disponible : {$this->available_balance} j., requis : {$this->estimated_days} j.).";
            }
        }

        // 3. Overlap Check for the same teacher
        $overlapQuery = LeaveRequest::where('teacher_id', $this->form_teacher_id)
            ->where('status', '!=', 'Refusé')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start->format('Y-m-d'))
                          ->where('end_date', '>=', $end->format('Y-m-d'));
                  });
            });
        if ($this->leave_id) {
            $overlapQuery->where('id', '!=', $this->leave_id);
        }
        if ($overlapQuery->exists()) {
            $this->overlap_warning = "Le collaborateur a déjà un congé actif ou en attente sur cette période.";
        }

        // 4. Check if interim teacher is on leave
        if ($this->form_interim_teacher_id) {
            $interimOnLeave = LeaveRequest::where('teacher_id', $this->form_interim_teacher_id)
                ->where('status', 'Approuvé')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                      ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                      ->orWhere(function ($sub) use ($start, $end) {
                          $sub->where('start_date', '<=', $start->format('Y-m-d'))
                              ->where('end_date', '>=', $end->format('Y-m-d'));
                      });
                })
                ->exists();
            if ($interimOnLeave) {
                $this->interim_warning = "Attention: L'intérimaire désigné est lui-même en congé durant cette période.";
            }
        }

        // 5. Check type info messages
        if ($this->form_leave_type === 'Arrêt maladie') {
            if ($this->estimated_days > 3) {
                $this->type_info_message = "Note : Les arrêts maladie de plus de 3 jours nécessitent le dépôt d'un justificatif médical sous 48 heures.";
            } else {
                $this->type_info_message = "Note : Veuillez vous assurer que le collaborateur transmet un justificatif d'arrêt maladie.";
            }
        } elseif ($this->form_leave_type === 'Congé sans solde') {
            $this->type_info_message = "Alerte de paie : Ce congé n'est pas rémunéré et sera automatiquement déduit du salaire de base du mois correspondant.";
        }
    }

    public function saveLeaveRequest()
    {
        $this->validate([
            'form_teacher_id' => 'required|exists:teachers,id',
            'form_start_date' => 'required|date',
            'form_end_date' => 'required|date|after_or_equal:form_start_date',
            'form_reason' => 'required|string',
            'form_leave_status' => 'required|in:En attente,Approuvé,Refusé',
            'form_interim_teacher_id' => 'nullable|exists:teachers,id',
            'form_leave_type' => 'required|string',
        ]);

        $this->runRealtimeValidation();

        if (str_contains($this->overlap_warning, 'déjà un congé actif') || str_contains($this->overlap_warning, 'déjà un congé active')) {
            $this->addError('form_start_date', $this->overlap_warning);
            return;
        }

        if ($this->estimated_days > $this->available_balance && in_array($this->form_leave_type, ['RTT', 'Congé payé légal'])) {
            $this->addError('form_leave_type', "Validation refusée : Le solde disponible ({$this->available_balance} jours) est insuffisant pour couvrir cette demande ({$this->estimated_days} jours).");
            return;
        }

        LeaveRequest::updateOrCreate(
            ['id' => $this->leave_id],
            [
                'teacher_id' => $this->form_teacher_id,
                'start_date' => $this->form_start_date,
                'end_date' => $this->form_end_date,
                'reason' => $this->form_reason,
                'status' => $this->form_leave_status,
                'interim_teacher_id' => $this->form_interim_teacher_id ?: null,
                'type' => $this->form_leave_type,
            ]
        );

        $this->isLeaveOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Demande de congé enregistrée.');
    }

    public function approveLeave($id)
    {
        $l = LeaveRequest::findOrFail($id);
        $l->update(['status' => 'Approuvé']);
        
        // Also update collaborator status to 'En congé'
        $l->teacher->update(['status' => 'En congé']);
        
        $this->loadData();
        session()->flash('success', 'Demande de congé approuvée.');
    }

    public function rejectLeave($id)
    {
        $l = LeaveRequest::findOrFail($id);
        $l->update(['status' => 'Refusé']);
        
        // If collaborator was on leave, make him active
        if ($l->teacher->status === 'En congé') {
            $l->teacher->update(['status' => 'Actif']);
        }
        
        $this->loadData();
        session()->flash('success', 'Demande de congé refusée.');
    }

    public function deleteLeaveRequest($id)
    {
        LeaveRequest::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('success', 'Demande de congé supprimée.');
    }

    // --- Timesheet Actions ---
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

        // Calculate total hours
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

    // --- Payslips Actions ---
    public function updatedFormPsTeacherId() { $this->autoCalculatePayslipDeductions(); }
    public function updatedFormPsMonthYear() { $this->autoCalculatePayslipDeductions(); }

    public function autoCalculatePayslipDeductions()
    {
        if (!$this->form_ps_teacher_id || !$this->form_ps_month_year) {
            return;
        }

        $teacher = Teacher::find($this->form_ps_teacher_id);
        if (!$teacher) return;

        // Auto populate base salary
        $this->form_ps_base_salary = floatval($teacher->salary);

        // Parse month_year (e.g. '07-2026')
        try {
            $monthStart = Carbon::createFromFormat('m-Y', $this->form_ps_month_year)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('m-Y', $this->form_ps_month_year)->endOfMonth();
        } catch (\Exception $e) {
            return;
        }

        // Find approved unpaid leaves during this month
        $unpaidLeaves = LeaveRequest::where('teacher_id', $this->form_ps_teacher_id)
            ->where('type', 'Congé sans solde')
            ->where('status', 'Approuvé')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                  ->orWhereBetween('end_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                  ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                      $sub->where('start_date', '<=', $monthStart->format('Y-m-d'))
                          ->where('end_date', '>=', $monthEnd->format('Y-m-d'));
                  });
            })
            ->get();

        $unpaidDays = 0;
        foreach ($unpaidLeaves as $leave) {
            $leaveStart = $leave->start_date;
            $leaveEnd = $leave->end_date;

            // Clamp to current month
            $start = $leaveStart->lt($monthStart) ? $monthStart->copy() : $leaveStart->copy();
            $end = $leaveEnd->gt($monthEnd) ? $monthEnd->copy() : $leaveEnd->copy();

            $unpaidDays += $start->diffInDays($end) + 1;
        }

        if ($unpaidDays > 0) {
            $deduction = round(($teacher->salary / 30) * $unpaidDays, 2);
            $this->form_ps_deductions = $deduction;
            $this->form_ps_notes = "Déduction de {$unpaidDays} jours de congé sans solde pour un total de {$deduction} $.";
        } else {
            $this->form_ps_deductions = 0.00;
            $this->form_ps_notes = "";
        }
    }

    public function createPayslip()
    {
        $this->resetValidation();
        $this->reset(['payslip_id', 'form_ps_teacher_id', 'form_ps_month_year', 'form_ps_base_salary', 'form_ps_bonus', 'form_ps_deductions', 'form_ps_status', 'form_ps_payment_date', 'form_ps_payment_method', 'form_ps_notes']);
        $this->form_ps_month_year = date('m-Y');
        $this->form_ps_status = 'Brouillon';
        $this->isPayslipOffcanvasOpen = true;
    }

    public function editPayslip($id)
    {
        $this->resetValidation();
        $p = Payslip::findOrFail($id);
        $this->payslip_id = $p->id;
        $this->form_ps_teacher_id = $p->teacher_id;
        $this->form_ps_month_year = $p->month_year;
        $this->form_ps_base_salary = $p->base_salary;
        $this->form_ps_bonus = $p->bonus;
        $this->form_ps_deductions = $p->deductions;
        $this->form_ps_status = $p->status;
        $this->form_ps_payment_date = $p->payment_date?->format('Y-m-d');
        $this->form_ps_payment_method = $p->payment_method;
        $this->form_ps_notes = $p->notes;
        $this->isPayslipOffcanvasOpen = true;
    }

    public function savePayslip()
    {
        $this->validate([
            'form_ps_teacher_id' => 'required|exists:teachers,id',
            'form_ps_month_year' => 'required|string',
            'form_ps_base_salary' => 'required|numeric',
        ]);

        $base = floatval($this->form_ps_base_salary);
        $bonus = floatval($this->form_ps_bonus) ?: 0;
        $deductions = floatval($this->form_ps_deductions) ?: 0;
        $net = $base + $bonus - $deductions;

        Payslip::updateOrCreate(
            ['id' => $this->payslip_id],
            [
                'teacher_id' => $this->form_ps_teacher_id,
                'month_year' => $this->form_ps_month_year,
                'base_salary' => $base,
                'bonus' => $bonus,
                'deductions' => $deductions,
                'net_salary' => $net,
                'status' => $this->form_ps_status,
                'payment_date' => $this->form_ps_payment_date,
                'payment_method' => $this->form_ps_payment_method,
                'notes' => $this->form_ps_notes,
            ]
        );

        $this->isPayslipOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Bulletin de paie enregistré.');
    }

    public function markAsPaid($id)
    {
        $p = Payslip::findOrFail($id);
        $p->update([
            'status' => 'Payé',
            'payment_date' => now(),
            'payment_method' => 'Virement',
        ]);
        $this->loadData();
        session()->flash('success', 'Bulletin de paie marqué comme payé.');
    }

    public function deletePayslip($id)
    {
        Payslip::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('success', 'Bulletin de paie supprimé.');
    }

    public function viewPayslip($id)
    {
        $this->viewing_payslip_id = $id;
    }

    public function closePayslipView()
    {
        $this->viewing_payslip_id = null;
    }

    public function openDetailedPayrollModal()
    {
        $this->filter_teacher_id = '';
        $this->payroll_start_date = date('Y-01-01');
        $this->payroll_end_date = date('Y-m-d');
        $this->isDetailedPayrollModalOpen = true;
    }

    public function closeDetailedPayrollModal()
    {
        $this->isDetailedPayrollModalOpen = false;
    }

    public function exportSinglePayslip($id)
    {
        $payslip = Payslip::with('teacher')->findOrFail($id);
        
        // Fetch School Information
        $school = auth()->user()->school ?? \App\Models\School::first();
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
        $uniqueCode = strtoupper(substr(md5(time() . $payslip->id), 0, 10));

        // Digital signature QR data
        $qrData = "Yeschool Bulletin de Paie\nCode: $uniqueCode\nEtablissement: $schoolName\nEmploye: {$payslip->teacher->first_name} {$payslip->teacher->last_name}\nMois: {$payslip->month_year}\nNet: $" . number_format($payslip->net_salary, 2) . "\nCertifie Conforme.";

        // Fetch and base64-encode QR code
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
            // Fallback
        }

        $data = [
            'payslip' => $payslip,
            'title' => "Bulletin de Paie - " . $payslip->month_year,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolPhone' => $schoolPhone,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'uniqueCode' => $uniqueCode,
            'qrCodeBase64' => $qrCodeBase64,
            'qrData' => $qrData,
            'footer' => "Généré par Yeschool - " . date('d/m/Y H:i')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.single-payslip', $data)->setPaper('a4', 'portrait');
        
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'bulletin_paie_' . $uniqueCode . '.pdf');
    }

    public function exportDetailedPayrollSheet()
    {
        $this->validate([
            'filter_teacher_id' => 'required|exists:teachers,id',
            'payroll_start_date' => 'required|date',
            'payroll_end_date' => 'required|date|after_or_equal:payroll_start_date',
        ]);

        $teacher = Teacher::findOrFail($this->filter_teacher_id);
        
        // Fetch payslips within period
        $payslips = Payslip::where('teacher_id', $this->filter_teacher_id)
            ->where('status', 'Payé')
            ->whereBetween('payment_date', [$this->payroll_start_date, $this->payroll_end_date])
            ->orderBy('payment_date')
            ->get();

        // Calculate Totals
        $totalBase = $payslips->sum('base_salary');
        $totalBonus = $payslips->sum('bonus');
        $totalDeductions = $payslips->sum('deductions');
        $totalNet = $payslips->sum('net_salary');

        // Fetch School Information
        $school = auth()->user()->school ?? \App\Models\School::first();
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

        // Period Formatting
        $formattedStart = \Carbon\Carbon::parse($this->payroll_start_date)->format('d/m/Y');
        $formattedEnd = \Carbon\Carbon::parse($this->payroll_end_date)->format('d/m/Y');
        $periodLabel = "Période du {$formattedStart} au {$formattedEnd}";

        // Digital signature QR data
        $qrData = "Yeschool Fiche De Paie\nCode: $uniqueCode\nEtablissement: $schoolName\nEmploye: {$teacher->first_name} {$teacher->last_name}\nPeriod: $periodLabel\nTotal Net: $" . number_format($totalNet, 2) . "\nCertifie Conforme.";

        // Fetch and base64-encode QR code
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
            // Fallback
        }

        $data = [
            'teacher' => $teacher,
            'payslips' => $payslips,
            'totalBase' => $totalBase,
            'totalBonus' => $totalBonus,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'periodLabel' => $periodLabel,
            'title' => "Fiche Détaillée de Paie - " . $teacher->first_name . ' ' . $teacher->last_name,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolPhone' => $schoolPhone,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'uniqueCode' => $uniqueCode,
            'qrCodeBase64' => $qrCodeBase64,
            'qrData' => $qrData,
            'footer' => "Généré par Yeschool - " . date('d/m/Y H:i')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.detailed-payroll', $data)->setPaper('a4', 'portrait');
        
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        $this->isDetailedPayrollModalOpen = false;

        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'fiche_paie_' . $uniqueCode . '.pdf');
    }
}
