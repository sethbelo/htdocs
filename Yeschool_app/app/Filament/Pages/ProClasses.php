<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Auth;

class ProClasses extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-classes';
    protected static ?string $title = 'Classes & Niveaux';

    public $classes = [];
    public $searchQuery = '';
    public $levelFilter = 'all';

    // Form fields
    public $class_id;
    public $name;
    public $level;
    public $capacity = 30;
    public $academic_year = '2026-2027';
    public $status = 'Active';
    public $enrolled_students = 0;

    // UI state
    public $isOffcanvasOpen = false;
    public $editMode = false;
    public $viewMode = false;
    public $activeClass = null;

    public $head_teacher_id = null;
    public $teachers = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'level' => 'required|string|max:255',
        'capacity' => 'required|integer|min:1',
        'academic_year' => 'required|string|max:255',
        'status' => 'required|string',
        'head_teacher_id' => 'nullable|exists:teachers,id',
    ];

    public function mount()
    {
        $this->loadClasses();
        $this->teachers = \App\Models\Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
    }

    public function loadClasses()
    {
        $query = SchoolClass::with('headTeacher');

        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $assignedClassIds = \App\Models\Course::where('teacher_id', $teacherId)
                ->pluck('school_class_id')
                ->unique()
                ->toArray();
            $query->whereIn('id', $assignedClassIds);
        }
        
        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        if ($this->levelFilter && $this->levelFilter !== 'all') {
            $query->where('level', $this->levelFilter);
        }

        $this->classes = $query->latest()->get()->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadClasses();
    }

    public function updatedLevelFilter()
    {
        $this->loadClasses();
    }

    public function createClass()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $this->reset(['class_id', 'name', 'level', 'head_teacher_id']);
        $this->capacity = 30;
        $this->academic_year = '2026-2027';
        $this->status = 'Active';
        $this->enrolled_students = 0;
        
        $this->editMode = false;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function viewClass($id)
    {
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $hasCourse = \App\Models\Course::where('teacher_id', $teacherId)
                ->where('school_class_id', $id)
                ->exists();
            if (!$hasCourse) {
                abort(403, 'Accès non autorisé.');
            }
        }

        $class = SchoolClass::with('headTeacher')->findOrFail($id);
        $this->activeClass = $class->toArray();
        
        $this->viewMode = true;
        $this->editMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function editClass($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->resetValidation();
        $class = SchoolClass::findOrFail($id);
        $this->class_id = $class->id;
        $this->name = $class->name;
        $this->level = $class->level;
        $this->capacity = $class->capacity;
        $this->academic_year = $class->academic_year;
        $this->status = $class->status;
        $this->enrolled_students = $class->enrolled_students;
        $this->head_teacher_id = $class->head_teacher_id;
        
        $this->editMode = true;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function saveClass()
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        $this->validate();

        SchoolClass::updateOrCreate(
            ['id' => $this->class_id],
            [
                'name' => $this->name,
                'level' => $this->level,
                'capacity' => $this->capacity,
                'academic_year' => $this->academic_year,
                'status' => $this->status,
                'enrolled_students' => $this->enrolled_students ?? 0,
                'head_teacher_id' => $this->head_teacher_id,
            ]
        );

        $this->isOffcanvasOpen = false;
        $this->loadClasses();
    }

    public function deleteClass($id)
    {
        abort_if(Auth::user()->hasRole('Enseignant'), 403, 'Accès non autorisé.');
        SchoolClass::findOrFail($id)->delete();
        $this->loadClasses();
    }
}
