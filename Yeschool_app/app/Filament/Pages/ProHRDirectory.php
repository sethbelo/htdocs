<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use Illuminate\Support\Carbon;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ProHRDirectory extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-directory';
    protected static ?string $title = 'Annuaire & Recrutement';

    // Disable default navigation registration, we link it manually in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $personnelList = [];
    public $filter_personnel_status = 'all';
    public $viewMode = 'list'; // 'list' or 'grid'
    public $departmentsList = [];
    public $currentTab = 'annuaire'; // 'annuaire' or 'idcard'
    public $selectedPersonnelForCardId = null;
    public $cardColorTheme = 'tan'; // 'tan', 'navy', 'emerald', 'royal'
    public $cardType = 'pvc'; // 'pvc', 'biometric', 'nfc'

    // Personnel Form Fields
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

    // Additional Fields
    public $form_civil_status = 'Célibataire';
    public $form_spouse_name;
    public $form_children_count = 0;
    public $form_children_names;
    public $form_academic_qualification;
    public $form_job_type;
    public $form_folder_number;
    public $form_manager_name;
    public $form_place_of_birth;
    public $form_acting_as;
    public $form_job;
    public $form_department_id;

    // Step-by-step wizard state
    public $form_step = 1;

    public function goToStep($targetStep)
    {
        if ($targetStep > $this->form_step) {
            if ($this->form_step === 1) {
                $this->validate([
                    'form_first_name' => 'required|string',
                    'form_last_name' => 'required|string',
                ]);
            }

            if ($this->form_step === 3 || ($this->form_step < 3 && $targetStep > 3)) {
                $this->validate([
                    'form_first_name' => 'required|string',
                    'form_last_name' => 'required|string',
                    'form_specialty' => 'required|string',
                ]);
            }
        }

        $this->form_step = $targetStep;
    }

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;
    public $confirmDeleteAction;
    public $confirmDeleteMessage;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        
        // Ensure default departments are seeded
        $defaults = ['Enseignant', 'Comptable', 'RH', 'Administration', 'Nettoyage', 'Sécurité'];
        foreach ($defaults as $name) {
            \App\Models\Department::firstOrCreate([
                'name' => $name
            ], [
                'description' => "Département regroupant le personnel de type {$name}."
            ]);
        }

        $this->loadData();
    }

    public function loadData()
    {
        $this->departmentsList = \App\Models\Department::orderBy('name')->get()->toArray();

        $query = Teacher::with('department');
        if ($this->filter_personnel_status !== 'all') {
            $query->where('status', $this->filter_personnel_status);
        }
        $teachers = $query->orderBy('last_name')->get();

        $grouped = [];
        foreach ($teachers as $t) {
            $deptName = $t->department ? $t->department->name : 'Sans département';
            $grouped[$deptName][] = $t->toArray();
        }

        $this->personnelList = $grouped;
    }

    public function getSelectedPersonnelProperty()
    {
        if (!$this->selectedPersonnelForCardId) {
            $first = Teacher::orderBy('last_name')->first();
            $this->selectedPersonnelForCardId = $first ? $first->id : null;
        }
        return Teacher::with('department')->find($this->selectedPersonnelForCardId);
    }

    public function getTeachersListProperty()
    {
        return Teacher::orderBy('last_name')->get();
    }

    public function updatedFilterPersonnelStatus()
    {
        $this->loadData();
    }

    public function createPersonnel()
    {
        $this->resetValidation();
        $this->reset(['personnel_id', 'form_first_name', 'form_last_name', 'form_email', 'form_phone', 'form_specialty', 'form_salary', 'form_status', 'form_contract_signed_at', 'form_contract_ended_at', 'form_recruitment_notes', 'form_date_of_birth', 'avatar_upload', 'avatar_webcam', 'avatar_path', 'cv_upload', 'cv_path', 'cover_letter_upload', 'cover_letter_path', 'form_civil_status', 'form_spouse_name', 'form_children_count', 'form_children_names', 'form_academic_qualification', 'form_job_type', 'form_folder_number', 'form_manager_name', 'form_place_of_birth', 'form_acting_as', 'form_job', 'form_department_id']);
        $this->form_civil_status = 'Célibataire';
        $this->form_children_count = 0;
        $this->form_status = 'Candidat';
        $this->form_step = 1;
        $this->form_folder_number = 'DOS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
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
        $this->form_contract_signed_at = $p->contract_signed_at ? Carbon::parse($p->contract_signed_at)->format('Y-m-d') : null;
        $this->form_contract_ended_at = $p->contract_ended_at ? Carbon::parse($p->contract_ended_at)->format('Y-m-d') : null;
        $this->form_recruitment_notes = $p->recruitment_notes;
        $this->form_date_of_birth = $p->date_of_birth ? Carbon::parse($p->date_of_birth)->format('Y-m-d') : null;
        $this->avatar_path = $p->avatar;
        $this->avatar_upload = null;
        $this->avatar_webcam = null;
        $this->cv_path = $p->cv_path;
        $this->cv_upload = null;
        $this->cover_letter_path = $p->cover_letter_path;
        $this->cover_letter_upload = null;

        // Family & Academic & Post
        $this->form_civil_status = $p->civil_status ?: 'Célibataire';
        $this->form_spouse_name = $p->spouse_name;
        $this->form_children_count = intval($p->children_count);
        $this->form_children_names = $p->children_names;
        $this->form_academic_qualification = $p->academic_qualification;
        $this->form_job_type = $p->job_type;
        $this->form_folder_number = $p->folder_number;
        $this->form_manager_name = $p->manager_name;
        $this->form_place_of_birth = $p->place_of_birth;
        $this->form_acting_as = $p->acting_as;
        $this->form_job = $p->job;
        $this->form_department_id = $p->department_id;
        
        $this->form_step = 1;
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
            'form_civil_status' => 'nullable|string',
            'form_spouse_name' => 'nullable|string',
            'form_children_count' => 'nullable|integer|min:0',
            'form_children_names' => 'nullable|string',
            'form_academic_qualification' => 'nullable|string',
            'form_job_type' => 'nullable|string',
            'form_folder_number' => 'nullable|string',
            'form_manager_name' => 'nullable|string',
            'form_place_of_birth' => 'nullable|string',
            'form_acting_as' => 'nullable|string',
            'form_job' => 'nullable|string',
            'form_department_id' => 'nullable|exists:departments,id',
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
                'civil_status' => $this->form_civil_status ?: 'Célibataire',
                'spouse_name' => ($this->form_civil_status === 'Marié') ? $this->form_spouse_name : null,
                'children_count' => intval($this->form_children_count),
                'children_names' => (intval($this->form_children_count) > 0) ? $this->form_children_names : null,
                'academic_qualification' => $this->form_academic_qualification,
                'job_type' => $this->form_job_type,
                'folder_number' => $this->form_folder_number,
                'manager_name' => $this->form_manager_name,
                'place_of_birth' => $this->form_place_of_birth,
                'acting_as' => $this->form_acting_as,
                'job' => $this->form_job,
                'department_id' => $this->form_department_id ?: null,
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
        session()->flash('success', 'Contrat signé. Le candidat est maintenant actif.');
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

    public function updatedAvatarUpload()
    {
        $this->avatar_webcam = null;
    }

    public function exportPersonnelPdf()
    {
        $query = Teacher::query();
        if ($this->filter_personnel_status !== 'all') {
            $query->where('status', $this->filter_personnel_status);
        }
        $personnel = $query->orderBy('last_name')->get();

        // Calculate stats
        $stats = [
            'total' => $personnel->count(),
            'actif' => $personnel->where('status', 'Actif')->count(),
            'candidat' => $personnel->where('status', 'Candidat')->count(),
            'inactif' => $personnel->where('status', 'Inactif')->count() + $personnel->where('status', 'Ancien')->count(),
        ];

        $pdfData = [
            'personnel' => $personnel,
            'stats' => $stats,
            'status_filter' => $this->filter_personnel_status,
            'date_generated' => now()->locale('fr')->isoFormat('DD MMMM YYYY [à] HH:mm'),
        ];

        return response()->streamDownload(function () use ($pdfData) {
            echo Pdf::loadView('pdf.personnel', $pdfData)->output();
        }, 'liste_collaborateurs_' . now()->format('Ymd_His') . '.pdf');
    }
}
