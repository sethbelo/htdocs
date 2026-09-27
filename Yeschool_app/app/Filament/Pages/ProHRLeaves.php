<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProHRLeaves extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-leaves';
    protected static ?string $title = 'Gestion des Congés';

    // Disable default navigation registration, we link it manually in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $leaveRequests = [];
    public $personnelList = [];
    public $filter_leave_status = 'all';

    // Tab & Leave balance state
    public $activeTab = 'gantt'; // 'gantt' | 'demandes' | 'soldes'
    public $leaveBalances = [];
    
    // Allowance Modal State
    public $isAllowanceModalOpen = false;
    public $editingAllowanceTeacherId;
    public $editingAllowanceValue;
    public $editingRttAllowanceValue;

    // Leave Request Form Fields
    public $isLeaveOffcanvasOpen = false;
    public $leave_id;
    public $form_teacher_id;
    public $form_start_date;
    public $form_end_date;
    public $form_reason;
    public $form_leave_status = 'En attente';
    public $form_interim_teacher_id;
    public $form_leave_type = 'Congé payé légal';

    // Interactive Alerts and Previews
    public $estimated_days = 0;
    public $available_balance = 0;
    public $post_balance = 0;
    public $overlap_warning = '';
    public $interim_warning = '';
    public $type_info_message = '';

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;
    public $confirmDeleteAction;
    public $confirmDeleteMessage;

    // Approve & Interim Modal State
    public $isApproveModalOpen = false;
    public $approvingLeaveId;
    public $interim_teacher_id;

    // Gantt Calendar State
    public $ganttYear;
    public $ganttMonth;
    public $ganttDays = [];
    public $ganttData = [];

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
        $this->ganttYear = intval(date('Y'));
        $this->ganttMonth = intval(date('m'));
        $this->loadData();
    }

    public function loadData()
    {
        $query = LeaveRequest::with(['teacher', 'interimTeacher']);
        if ($this->filter_leave_status !== 'all') {
            $query->where('status', $this->filter_leave_status);
        }
        $this->leaveRequests = $query->orderByDesc('created_at')->get()->toArray();
        $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
        $this->loadGanttData();
        $this->loadLeaveBalances();
    }

    public function updatedFilterLeaveStatus()
    {
        $this->loadData();
    }

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

        if ($this->form_leave_status === 'Approuvé') {
            $t = Teacher::find($this->form_teacher_id);
            if ($t) {
                $t->update(['status' => 'En congé']);
            }
        }

        $this->isLeaveOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Demande de congé enregistrée.');
    }

    public function startApproveLeave($id)
    {
        $this->approvingLeaveId = $id;
        $this->interim_teacher_id = null;
        $this->isApproveModalOpen = true;
    }

    public function confirmApproveLeave()
    {
        if (!$this->approvingLeaveId) return;

        $l = LeaveRequest::findOrFail($this->approvingLeaveId);
        $l->update([
            'status' => 'Approuvé',
            'interim_teacher_id' => $this->interim_teacher_id ?: null
        ]);
        
        // Also update collaborator status to 'En congé'
        $l->teacher->update(['status' => 'En congé']);
        
        $this->isApproveModalOpen = false;
        $this->approvingLeaveId = null;
        $this->interim_teacher_id = null;
        
        $this->loadData();
        session()->flash('success', 'Demande de congé approuvée avec désignation d\'intérim.');
    }

    public function loadGanttData()
    {
        if (!$this->ganttYear) {
            $this->ganttYear = intval(date('Y'));
        }
        if (!$this->ganttMonth) {
            $this->ganttMonth = intval(date('m'));
        }

        $startDate = Carbon::create($this->ganttYear, $this->ganttMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->ganttYear, $this->ganttMonth, 1)->endOfMonth();

        $daysInMonth = $startDate->daysInMonth;
        $this->ganttDays = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $this->ganttDays[] = $i;
        }

        // Fetch leave requests overlapping this month
        $leaves = LeaveRequest::with(['teacher', 'interimTeacher'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate->format('Y-m-d'))
                            ->where('end_date', '>=', $endDate->format('Y-m-d'));
                      });
            })
            ->get();

        $this->ganttData = [];
        foreach ($leaves as $l) {
            $rowDays = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayStr = sprintf('%04d-%02d-%02d', $this->ganttYear, $this->ganttMonth, $d);
                $isLeave = ($dayStr >= $l->start_date->format('Y-m-d') && $dayStr <= $l->end_date->format('Y-m-d'));
                $rowDays[$d] = $isLeave;
            }

            $this->ganttData[] = [
                'id' => $l->id,
                'teacher_name' => $l->teacher->first_name . ' ' . $l->teacher->last_name,
                'specialty' => $l->teacher->specialty ?: 'Enseignant',
                'start_date' => $l->start_date->format('d/m'),
                'end_date' => $l->end_date->format('d/m'),
                'reason' => $l->reason,
                'status' => $l->status,
                'type' => $l->type ?? 'Congé payé légal',
                'interim_name' => $l->interimTeacher ? ($l->interimTeacher->first_name . ' ' . $l->interimTeacher->last_name) : null,
                'days' => $rowDays
            ];
        }
    }

    public function previousGanttMonth()
    {
        $date = Carbon::create($this->ganttYear, $this->ganttMonth, 1)->subMonth();
        $this->ganttYear = $date->year;
        $this->ganttMonth = $date->month;
        $this->loadGanttData();
    }

    public function nextGanttMonth()
    {
        $date = Carbon::create($this->ganttYear, $this->ganttMonth, 1)->addMonth();
        $this->ganttYear = $date->year;
        $this->ganttMonth = $date->month;
        $this->loadGanttData();
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

    public function loadLeaveBalances()
    {
        $currentYear = date('Y');
        $teachers = Teacher::whereIn('status', ['Actif', 'En congé'])->orderBy('last_name')->get();

        $this->leaveBalances = [];
        foreach ($teachers as $t) {
            // Get all approved leaves of this year
            $approvedLeaves = LeaveRequest::where('teacher_id', $t->id)
                ->where('status', 'Approuvé')
                ->where(function ($query) use ($currentYear) {
                    $query->whereYear('start_date', $currentYear)
                          ->orWhereYear('end_date', $currentYear);
                })
                ->get();

            $cpUsed = 0;
            $rttUsed = 0;
            $maladieUsed = 0;
            $sansSoldeUsed = 0;

            foreach ($approvedLeaves as $l) {
                $startDate = $l->start_date;
                $endDate = $l->end_date;

                // Clamp dates to current year
                if ($startDate->format('Y') < $currentYear) {
                    $startDate = Carbon::create($currentYear, 1, 1);
                }
                if ($endDate->format('Y') > $currentYear) {
                    $endDate = Carbon::create($currentYear, 12, 31);
                }

                $type = $l->type ?? 'Congé payé légal';
                $days = LeaveRequest::calculateDays($startDate->format('Y-m-d'), $endDate->format('Y-m-d'), $type);

                if ($type === 'RTT') {
                    $rttUsed += $days;
                } elseif ($type === 'Arrêt maladie') {
                    $maladieUsed += $days;
                } elseif ($type === 'Congé sans solde') {
                    $sansSoldeUsed += $days;
                } else {
                    $cpUsed += $days;
                }
            }

            $cpAllowance = $t->annual_leave_allowance ?? 30;
            $rttAllowance = $t->rtt_allowance ?? 10;

            $this->leaveBalances[] = [
                'teacher_id' => $t->id,
                'teacher_name' => $t->first_name . ' ' . $t->last_name,
                'specialty' => $t->specialty ?: 'Enseignant',
                'cp_allowance' => $cpAllowance,
                'cp_used' => $cpUsed,
                'cp_available' => max(0, $cpAllowance - $cpUsed),
                'rtt_allowance' => $rttAllowance,
                'rtt_used' => $rttUsed,
                'rtt_available' => max(0, $rttAllowance - $rttUsed),
                'maladie_used' => $maladieUsed,
                'sans_solde_used' => $sansSoldeUsed,
            ];
        }
    }

    public function startEditAllowance($teacherId, $currentAllowance, $currentRttAllowance = 10)
    {
        $this->editingAllowanceTeacherId = $teacherId;
        $this->editingAllowanceValue = $currentAllowance;
        $this->editingRttAllowanceValue = $currentRttAllowance;
        $this->isAllowanceModalOpen = true;
    }

    public function saveAllowance()
    {
        if (!$this->editingAllowanceTeacherId) return;

        $t = Teacher::findOrFail($this->editingAllowanceTeacherId);
        $t->update([
            'annual_leave_allowance' => $this->editingAllowanceValue,
            'rtt_allowance' => $this->editingRttAllowanceValue,
        ]);

        $this->isAllowanceModalOpen = false;
        $this->editingAllowanceTeacherId = null;
        $this->editingAllowanceValue = null;
        $this->editingRttAllowanceValue = null;

        $this->loadData();
        session()->flash('success', 'Soldes annuels (Congés payés & RTT) mis à jour avec succès.');
    }
}
