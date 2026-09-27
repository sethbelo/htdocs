<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ClassJournal;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\CourseNote;
use Illuminate\Support\Facades\Auth;

class ProClassJournals extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-class-journals';
    protected static ?string $title = 'Journal de classe';

    // Disable default Filament navigation registration because we render it custom in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $journals = [];
    public $searchQuery = '';
    public $classFilter = 'all';

    // Form fields for Journal Entry
    public $journal_id;
    public $school_class_id;
    public $subject_id;
    public $date;
    public $start_time;
    public $end_time;
    public $title_lesson;
    public $context;

    // Form fields for Preparation Note (Note de cours)
    public $prep_note_id;
    public $prep_title;
    public $prep_content;
    public $is_published = false;

    // UI state
    public $isOffcanvasOpen = false;
    public $isPrepNoteOpen = false;
    public $editMode = false;
    public $viewMode = false;
    public $activeJournal = null;

    // Dropdowns data
    public $classes = [];
    public $subjects = [];

    protected $rules = [
        'school_class_id' => 'required|exists:school_classes,id',
        'subject_id' => 'required|exists:subjects,id',
        'date' => 'required|date',
        'start_time' => 'required|string',
        'end_time' => 'required|string',
        'title_lesson' => 'required|string|max:255',
        'context' => 'nullable|string',
    ];

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->loadData();
    }

    public function loadData()
    {
        $user = Auth::user();
        $isTeacher = $user->hasRole('Enseignant');
        
        $teacherId = 0;
        if ($isTeacher) {
            $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
                ?? \App\Models\Teacher::find(3) 
                ?? \App\Models\Teacher::first();
            $teacherId = $teacher ? $teacher->id : 0;
        }

        // Load dropdowns
        if ($isTeacher && $teacherId) {
            $assignedClassIds = \App\Models\Course::where('teacher_id', $teacherId)->pluck('school_class_id')->unique()->toArray();
            $this->classes = SchoolClass::whereIn('id', $assignedClassIds)->get()->toArray();
            
            $assignedSubjectIds = \App\Models\Course::where('teacher_id', $teacherId)->pluck('subject_id')->unique()->toArray();
            $this->subjects = Subject::whereIn('id', $assignedSubjectIds)->get()->toArray();
        } else {
            $this->classes = SchoolClass::all()->toArray();
            $this->subjects = Subject::all()->toArray();
        }

        // Query Journals
        $query = ClassJournal::with(['teacher', 'schoolClass', 'subject', 'courseNote']);

        if ($isTeacher && $teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        if ($this->searchQuery) {
            $query->where('title', 'like', '%' . $this->searchQuery . '%');
        }

        if ($this->classFilter && $this->classFilter !== 'all') {
            $query->where('school_class_id', $this->classFilter);
        }

        $this->journals = $query->orderBy('date', 'desc')->orderBy('start_time', 'asc')->get()->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadData();
    }

    public function updatedClassFilter()
    {
        $this->loadData();
    }

    public function createJournal()
    {
        $this->resetValidation();
        $this->reset(['journal_id', 'school_class_id', 'subject_id', 'start_time', 'end_time', 'title_lesson', 'context']);
        $this->date = date('Y-m-d');
        $this->editMode = false;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function editJournal($id)
    {
        $this->resetValidation();
        $journal = ClassJournal::findOrFail($id);
        
        $this->journal_id = $journal->id;
        $this->school_class_id = $journal->school_class_id;
        $this->subject_id = $journal->subject_id;
        $this->date = $journal->date;
        $this->start_time = $journal->start_time;
        $this->end_time = $journal->end_time;
        $this->title_lesson = $journal->title;
        $this->context = $journal->context;

        $this->editMode = true;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function saveJournal()
    {
        $this->validate();

        $user = Auth::user();
        $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
            ?? \App\Models\Teacher::find(3) 
            ?? \App\Models\Teacher::first();
        $teacherId = $teacher ? $teacher->id : 1;

        ClassJournal::updateOrCreate(
            ['id' => $this->journal_id],
            [
                'teacher_id' => $teacherId,
                'school_class_id' => $this->school_class_id,
                'subject_id' => $this->subject_id,
                'date' => $this->date,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'title' => $this->title_lesson,
                'context' => $this->context,
            ]
        );

        $this->isOffcanvasOpen = false;
        $this->loadData();
    }

    public function deleteJournal($id)
    {
        ClassJournal::findOrFail($id)->delete();
        $this->loadData();
    }

    // Preparation Note Management
    public function managePrepNote($id)
    {
        $this->resetValidation();
        $journal = ClassJournal::with('courseNote')->findOrFail($id);
        
        $this->journal_id = $journal->id;
        $this->activeJournal = $journal->toArray();

        $note = $journal->courseNote;
        if ($note) {
            $this->prep_note_id = $note->id;
            $this->prep_title = $note->title;
            $this->prep_content = $note->content;
            $this->is_published = $note->is_published;
        } else {
            $this->prep_note_id = null;
            $this->prep_title = 'Préparation : ' . $journal->title;
            $this->prep_content = '';
            $this->is_published = false;
        }

        $this->isPrepNoteOpen = true;
    }

    public function savePrepNote()
    {
        $this->validate([
            'prep_title' => 'required|string|max:255',
            'prep_content' => 'required|string',
        ]);

        $user = Auth::user();
        $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
            ?? \App\Models\Teacher::find(3) 
            ?? \App\Models\Teacher::first();
        $teacherId = $teacher ? $teacher->id : 1;

        CourseNote::updateOrCreate(
            ['id' => $this->prep_note_id],
            [
                'class_journal_id' => $this->journal_id,
                'teacher_id' => $teacherId,
                'title' => $this->prep_title,
                'content' => $this->prep_content,
                'is_published' => (bool)$this->is_published,
            ]
        );

        $this->isPrepNoteOpen = false;
        $this->loadData();
    }

    public function togglePublishNote($id)
    {
        $note = CourseNote::findOrFail($id);
        
        // Ensure only the author teacher can modify publishing
        $user = Auth::user();
        if ($user->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
                ?? \App\Models\Teacher::find(3) 
                ?? \App\Models\Teacher::first();
            if ($note->teacher_id !== ($teacher ? $teacher->id : 0)) {
                abort(403, 'Action non autorisée');
            }
        }

        $note->is_published = !$note->is_published;
        $note->save();
        $this->loadData();
    }
}
