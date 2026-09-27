<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Student;
use App\Models\SchoolClass;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class ProStudents extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-students';
    protected static ?string $title = 'Élèves';

    public $students = [];
    public $schoolClasses = [];
    public $searchQuery = '';
    public $classFilter = 'all';

    protected $queryString = [
        'classFilter' => ['except' => 'all'],
        'searchQuery' => ['except' => ''],
    ];

    public $avatar_upload;
    public $avatar_path;

    // Transfer Modal Fields
    public $isTransferModalOpen = false;
    public $transfer_student_id;
    public $transfer_student_name;
    public $transfer_current_class_name;
    public $transfer_class_id;

    // Form fields
    public $student_id;
    public $first_name;
    public $last_name;
    public $gender;
    public $date_of_birth;
    public $school_class_id;
    public $status = 'Active';
    public $matricule;
    public $auto_generate_matricule = true;
    public $parent_name;
    public $parent_phone;

    // UI state
    public $isOffcanvasOpen = false;
    public $editMode = false;
    public $viewMode = false;
    public $activeStudent = null;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gender' => 'required|in:M,F',
        'date_of_birth' => 'required|date',
        'school_class_id' => 'required|exists:school_classes,id',
        'status' => 'required|string',
        'parent_name' => 'nullable|string|max:255',
        'parent_phone' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->loadStudents();
    }

    public function loadClasses()
    {
        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            $selectedYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id 
                ?? \App\Models\AcademicYear::first()?->id;
        }

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if ($teacher) {
                $assignedClassIds = \App\Models\Course::where('teacher_id', $teacher->id)
                    ->where('academic_year_id', $selectedYearId)
                    ->pluck('school_class_id')
                    ->unique()
                    ->toArray();
                $this->schoolClasses = SchoolClass::whereIn('id', $assignedClassIds)->orderBy('level')->orderBy('name')->get()->toArray();
            } else {
                $this->schoolClasses = [];
            }
        } else {
            $this->schoolClasses = SchoolClass::orderBy('level')->orderBy('name')->get()->toArray();
        }
    }

    public function loadStudents()
    {
        $this->loadClasses();

        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            $selectedYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id 
                ?? \App\Models\AcademicYear::first()?->id;
            if ($selectedYearId) {
                session(['selected_academic_year_id' => $selectedYearId]);
            }
        }

        $query = Student::with('schoolClass');
        
        if ($selectedYearId) {
            $query->whereHas('enrollments', function($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId);
            });
        }

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $assignedClassIds = \App\Models\Course::where('teacher_id', $teacherId)
                ->where('academic_year_id', $selectedYearId)
                ->pluck('school_class_id')
                ->unique()
                ->toArray();
            
            $query->whereIn('school_class_id', $assignedClassIds);
        }

        if ($this->searchQuery) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%');
            });
        }

        if ($this->classFilter && $this->classFilter !== 'all') {
            if (Auth::user()->hasRole('Enseignant')) {
                $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
                $teacherId = $teacher ? $teacher->id : 0;
                $assignedClassIds = \App\Models\Course::where('teacher_id', $teacherId)
                    ->where('academic_year_id', $selectedYearId)
                    ->pluck('school_class_id')
                    ->unique()
                    ->toArray();
                if (in_array($this->classFilter, $assignedClassIds)) {
                    $query->where('school_class_id', $this->classFilter);
                } else {
                    $query->whereNull('id'); // unauthorized filter
                }
            } else {
                $query->where('school_class_id', $this->classFilter);
            }
        }

        $this->students = $query->latest()->get()->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadStudents();
    }

    public function updatedClassFilter()
    {
        $this->loadStudents();
    }

    public function createStudent()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $this->reset(['student_id', 'first_name', 'last_name', 'gender', 'date_of_birth', 'school_class_id', 'avatar_upload', 'avatar_path', 'matricule', 'parent_name', 'parent_phone']);
        $this->status = 'Active';
        $this->auto_generate_matricule = true;
        
        $this->editMode = false;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function viewStudent($id)
    {
        $student = Student::with('schoolClass')->findOrFail($id);
        $this->activeStudent = $student->toArray();
        
        $this->viewMode = true;
        $this->editMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function editStudent($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $student = Student::findOrFail($id);
        $this->student_id = $student->id;
        $this->first_name = $student->first_name;
        $this->last_name = $student->last_name;
        $this->gender = $student->gender;
        $this->date_of_birth = $student->date_of_birth;
        $this->school_class_id = $student->school_class_id;
        $this->status = $student->status;
        $this->avatar_path = $student->avatar;
        $this->avatar_upload = null;
        $this->matricule = $student->matricule;
        $this->auto_generate_matricule = false;
        $this->parent_name = $student->parent_name;
        $this->parent_phone = $student->parent_phone;
        
        $this->editMode = true;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function saveStudent()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->validate();

        $matriculeVal = $this->matricule;
        if ($this->auto_generate_matricule && !$this->student_id) {
            $codeEcole = \App\Models\Setting::get('school_code_ecole', '120409');
            $uniqueNum = rand(10000000, 99999999);
            $year = date('Y');
            $matriculeVal = "{$codeEcole}-{$uniqueNum}-{$year}";
        }

        $avatarPath = $this->avatar_path;
        if ($this->avatar_upload) {
            $this->validate([
                'avatar_upload' => 'image|max:1024'
            ]);
            $avatarPath = $this->avatar_upload->store('avatars', 'public');
        }

        Student::updateOrCreate(
            ['id' => $this->student_id],
            [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth,
                'school_class_id' => $this->school_class_id,
                'status' => $this->status,
                'avatar' => $avatarPath,
                'matricule' => $matriculeVal,
                'parent_name' => $this->parent_name,
                'parent_phone' => $this->parent_phone,
            ]
        );

        $this->isOffcanvasOpen = false;
        $this->loadStudents();
    }

    public function archiveStudent($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $student = Student::findOrFail($id);
        $student->update(['status' => 'Archived']);
        $this->loadStudents();
    }

    public function initiateTransfer($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $student = Student::with(['schoolClass', 'enrollments' => function($q) {
            $q->where('academic_year_id', session('selected_academic_year_id'));
        }])->findOrFail($id);

        $this->transfer_student_id = $student->id;
        $this->transfer_student_name = $student->first_name . ' ' . $student->last_name;

        // Current class for the selected year
        $selectedYearId = session('selected_academic_year_id');
        $enrollment = $student->enrollments->first();
        
        $this->transfer_current_class_name = $enrollment && $enrollment->schoolClass 
            ? $enrollment->schoolClass->name 
            : ($student->schoolClass ? $student->schoolClass->name : 'Aucune');

        $this->transfer_class_id = null;
        $this->isTransferModalOpen = true;
    }

    public function confirmTransfer()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->validate([
            'transfer_class_id' => 'required|exists:school_classes,id',
        ]);

        $selectedYearId = session('selected_academic_year_id');
        if (!$selectedYearId) {
            $selectedYearId = \App\Models\AcademicYear::where('is_active', true)->first()?->id 
                ?? \App\Models\AcademicYear::first()?->id;
        }

        // Update or Create Enrollment for selected year
        $enrollment = \App\Models\Enrollment::where('student_id', $this->transfer_student_id)
            ->where('academic_year_id', $selectedYearId)
            ->first();

        if ($enrollment) {
            $enrollment->update(['school_class_id' => $this->transfer_class_id]);
        } else {
            \App\Models\Enrollment::create([
                'student_id' => $this->transfer_student_id,
                'academic_year_id' => $selectedYearId,
                'school_class_id' => $this->transfer_class_id,
                'type' => 'reinscription',
                'status' => 'active',
            ]);
        }

        // Update active class on Student model
        $student = Student::findOrFail($this->transfer_student_id);
        $student->update(['school_class_id' => $this->transfer_class_id]);

        $this->isTransferModalOpen = false;
        $this->loadStudents();
        
        session()->flash('success', "L'élève a été transféré avec succès.");
    }
}
