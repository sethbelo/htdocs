<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\CourseNote;
use Illuminate\Support\Facades\Auth;

class ProCourseNotes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-course-notes';
    protected static ?string $title = 'Notes de cours';

    // Disable default Filament navigation registration because we render it custom in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $notes = [];
    public $searchQuery = '';
    public $publishFilter = 'all';
    public $archiveFilter = 'active'; // 'active', 'archived', 'all'

    // Form fields for editing/viewing
    public $prep_note_id;
    public $prep_title;
    public $prep_content;
    public $is_published = false;
    public $activeNote = null;

    // UI state
    public $isViewOpen = false;
    public $isEditOpen = false;

    protected $rules = [
        'prep_title' => 'required|string|max:255',
        'prep_content' => 'required|string',
    ];

    public function mount()
    {
        $this->loadNotes();
    }

    public function loadNotes()
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

        $query = CourseNote::with(['classJournal.schoolClass', 'classJournal.subject', 'teacher']);

        // Permissions scoping
        if ($isTeacher && $teacherId) {
            $query->where('teacher_id', $teacherId);
            
            if ($this->publishFilter === 'published') {
                $query->where('is_published', true);
            } elseif ($this->publishFilter === 'private') {
                $query->where('is_published', false);
            }

            // Archive scoping
            if ($this->archiveFilter === 'active') {
                $query->where('is_archived', false);
            } elseif ($this->archiveFilter === 'archived') {
                $query->where('is_archived', true);
            }
        } else {
            // Admins can only see published and non-archived notes
            $query->where('is_published', true)
                  ->where('is_archived', false);
        }

        if ($this->searchQuery) {
            $query->where('title', 'like', '%' . $this->searchQuery . '%');
        }

        $this->notes = $query->latest()->get()->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadNotes();
    }

    public function updatedPublishFilter()
    {
        $this->loadNotes();
    }

    public function updatedArchiveFilter()
    {
        $this->loadNotes();
    }

    public function viewNote($id)
    {
        $note = CourseNote::with(['classJournal.schoolClass', 'classJournal.subject', 'teacher'])->findOrFail($id);
        $this->activeNote = $note->toArray();
        $this->prep_note_id = $note->id;
        $this->prep_title = $note->title;
        $this->prep_content = $note->content;
        $this->is_published = $note->is_published;
        
        $this->isViewOpen = true;
    }

    public function editNote($id)
    {
        $note = CourseNote::findOrFail($id);
        
        // Check permission
        $user = Auth::user();
        if ($user->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
                ?? \App\Models\Teacher::find(3) 
                ?? \App\Models\Teacher::first();
            if ($note->teacher_id !== ($teacher ? $teacher->id : 0)) {
                abort(403, 'Action non autorisée');
            }
        }

        $this->prep_note_id = $note->id;
        $this->prep_title = $note->title;
        $this->prep_content = $note->content;
        $this->is_published = $note->is_published;
        $this->activeNote = $note->toArray();

        $this->isViewOpen = false;
        $this->isEditOpen = true;
    }

    public function saveNote()
    {
        $this->validate();
        
        $note = CourseNote::findOrFail($this->prep_note_id);
        $note->update([
            'title' => $this->prep_title,
            'content' => $this->prep_content,
            'is_published' => (bool)$this->is_published,
        ]);

        $this->isEditOpen = false;
        $this->loadNotes();
    }

    public function togglePublish($id)
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
        
        if ($this->activeNote && $this->activeNote['id'] === $id) {
            $this->is_published = $note->is_published;
            $this->activeNote['is_published'] = $note->is_published;
        }

        $this->loadNotes();
    }

    public function toggleArchive($id)
    {
        $note = CourseNote::findOrFail($id);
        
        // Ensure only the author teacher can archive
        $user = Auth::user();
        if ($user->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
                ?? \App\Models\Teacher::find(3) 
                ?? \App\Models\Teacher::first();
            if ($note->teacher_id !== ($teacher ? $teacher->id : 0)) {
                abort(403, 'Action non autorisée');
            }
        }

        $note->is_archived = !$note->is_archived;
        $note->save();

        if ($this->activeNote && $this->activeNote['id'] === $id) {
            $this->activeNote['is_archived'] = $note->is_archived;
        }

        $this->loadNotes();
    }

    public function deleteNote($id)
    {
        $note = CourseNote::findOrFail($id);

        // Ensure only the author teacher can delete
        $user = Auth::user();
        if ($user->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', $user->email)->first() 
                ?? \App\Models\Teacher::find(3) 
                ?? \App\Models\Teacher::first();
            if ($note->teacher_id !== ($teacher ? $teacher->id : 0)) {
                abort(403, 'Action non autorisée');
            }
        }

        $note->delete();
        $this->isViewOpen = false;
        $this->isEditOpen = false;
        $this->loadNotes();
    }

    public function exportPdf($id)
    {
        $note = CourseNote::with(['classJournal.schoolClass', 'classJournal.subject', 'teacher'])->findOrFail($id);

        // Fetch School Information
        $school = auth()->user()->school ?? \App\Models\School::first();
        $schoolName = $school ? $school->name : 'Yeschool Établissement';
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

        $data = [
            'noteId' => $note->id,
            'noteTitle' => $note->title,
            'noteContent' => $note->content,
            'teacherName' => ($note->teacher->first_name ?? '') . ' ' . ($note->teacher->last_name ?? ''),
            'className' => $note->classJournal->schoolClass->name ?? 'N/A',
            'subjectName' => $note->classJournal->subject->name ?? 'N/A',
            'lessonTitle' => $note->classJournal->title ?? 'N/A',
            'lessonDate' => $note->classJournal->date ?? date('Y-m-d'),
            'lessonHours' => ($note->classJournal->start_time ?? '00:00') . ' - ' . ($note->classJournal->end_time ?? '00:00'),
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'footer' => "Document pédagogique officiel - Yeschool " . date('Y') . " - Page 1/1"
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.course-note', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'fiche_preparation_' . $note->id . '.pdf');
    }
}
