<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Subject;
use App\Models\Course;
use App\Models\SchoolClass;
use App\Models\Teacher;

class ProSubjects extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-subjects';
    protected static ?string $title = 'Matières & Cours';

    // Tabs
    public $activeTab = 'matières'; // 'matières' or 'programme'

    // Lists
    public $schoolClasses = [];
    public $teachers = [];
    public $subjects = [];
    public $courses = [];

    // Filters for Course Program Grid
    public $selectedClassId = null;
    public $selectedTerm = 'Trimestre 1';

    // Search for Subjects
    public $subjectSearch = '';

    // Modals state control
    public $isSubjectModalOpen = false;
    public $isCourseModalOpen = false;

    // Form inputs for Subject
    public $subjectId = null;
    public $subjectName = '';
    public $subjectColor = '#4f46e5';

    // Form inputs for Course Assignment
    public $courseId = null;
    public $courseSubjectId = '';
    public $courseTeacherId = '';
    public $courseTerm = 'Trimestre 1';
    public $courseMaxScore = 20;
    public $courseCoefficient = 1;

    public function mount()
    {
        $this->loadData();
        
        // Auto-select first class if available
        if (!empty($this->schoolClasses)) {
            $this->selectedClassId = $this->schoolClasses[0]['id'];
        }
        
        $this->loadCoursesGrid();
    }

    public function loadData()
    {
        $this->schoolClasses = SchoolClass::orderBy('level')->orderBy('name')->get()->toArray();
        $this->teachers = Teacher::orderBy('last_name')->orderBy('first_name')->get()->toArray();
        $this->loadSubjects();
    }

    public function loadSubjects()
    {
        $query = Subject::query();
        if ($this->subjectSearch) {
            $query->where('name', 'like', '%' . $this->subjectSearch . '%');
        }
        $this->subjects = $query->orderBy('name')->get()->toArray();
    }

    public function updatedSubjectSearch()
    {
        $this->loadSubjects();
    }

    public function updatedSelectedClassId()
    {
        $this->loadCoursesGrid();
    }

    public function updatedSelectedTerm()
    {
        $this->loadCoursesGrid();
    }

    public function loadCoursesGrid()
    {
        if (!$this->selectedClassId) {
            $this->courses = [];
            return;
        }

        $this->courses = Course::with(['subject', 'teacher'])
            ->where('school_class_id', $this->selectedClassId)
            ->where('term', $this->selectedTerm)
            ->get()
            ->toArray();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'programme') {
            $this->loadCoursesGrid();
        } else {
            $this->loadSubjects();
        }
    }

    // --- GENERAL SUBJECT CRUD HANDLERS ---

    public function openNewSubjectModal()
    {
        $this->resetSubjectForm();
        $this->isSubjectModalOpen = true;
    }

    public function openEditSubjectModal($id)
    {
        $subject = Subject::find($id);
        if ($subject) {
            $this->subjectId = $subject->id;
            $this->subjectName = $subject->name;
            $this->subjectColor = $subject->color;
            $this->isSubjectModalOpen = true;
        }
    }

    public function resetSubjectForm()
    {
        $this->subjectId = null;
        $this->subjectName = '';
        $this->subjectColor = '#4f46e5';
    }

    public function saveSubject()
    {
        $this->validate([
            'subjectName' => 'required|string|max:255',
            'subjectColor' => 'required|string',
        ]);

        Subject::updateOrCreate(
            ['id' => $this->subjectId],
            [
                'name' => $this->subjectName,
                'color' => $this->subjectColor,
            ]
        );

        $this->resetSubjectForm();
        $this->isSubjectModalOpen = false;
        $this->loadSubjects();
        session()->flash('success', 'Matière enregistrée avec succès.');
    }

    public function deleteSubject($id)
    {
        Subject::destroy($id);
        $this->loadSubjects();
        $this->loadCoursesGrid();
        session()->flash('success', 'Matière supprimée avec succès.');
    }

    // --- COURSE PROGRAM ASSIGNMENT HANDLERS ---

    public function openNewCourseModal()
    {
        $this->resetCourseForm();
        $this->courseTerm = $this->selectedTerm;
        $this->isCourseModalOpen = true;
    }

    public function openEditCourseModal($id)
    {
        $course = Course::find($id);
        if ($course) {
            $this->courseId = $course->id;
            $this->courseSubjectId = $course->subject_id;
            $this->courseTeacherId = $course->teacher_id;
            $this->courseTerm = $course->term;
            $this->courseMaxScore = $course->max_score;
            $this->courseCoefficient = $course->coefficient;
            $this->isCourseModalOpen = true;
        }
    }

    public function resetCourseForm()
    {
        $this->courseId = null;
        $this->courseSubjectId = '';
        $this->courseTeacherId = '';
        $this->courseTerm = 'Trimestre 1';
        $this->courseMaxScore = 20;
        $this->courseCoefficient = 1;
    }

    public function saveCourse()
    {
        $this->validate([
            'courseSubjectId' => 'required|exists:subjects,id',
            'courseTeacherId' => 'nullable|exists:teachers,id',
            'courseTerm' => 'required|string',
            'courseMaxScore' => 'required|numeric|min:1',
            'courseCoefficient' => 'required|integer|min:1',
        ]);

        if (!$this->selectedClassId) {
            session()->flash('error', 'Veuillez sélectionner une classe.');
            return;
        }

        Course::updateOrCreate(
            ['id' => $this->courseId],
            [
                'school_class_id' => $this->selectedClassId,
                'subject_id' => $this->courseSubjectId,
                'teacher_id' => $this->courseTeacherId ?: null,
                'term' => $this->courseTerm,
                'max_score' => $this->courseMaxScore,
                'coefficient' => $this->courseCoefficient,
            ]
        );

        $this->resetCourseForm();
        $this->isCourseModalOpen = false;
        $this->loadCoursesGrid();
        session()->flash('success', 'Cours programmé avec succès.');
    }

    public function deleteCourse($id)
    {
        Course::findOrFail($id)->delete();
        $this->loadCoursesGrid();
        session()->flash('success', 'Cours retiré du programme.');
    }

    public function exportPdf()
    {
        if (!$this->selectedClassId) {
            return;
        }

        $schoolClass = SchoolClass::find($this->selectedClassId);
        if (!$schoolClass) {
            return;
        }

        $coursesList = Course::with(['subject', 'teacher'])
            ->where('school_class_id', $this->selectedClassId)
            ->where('term', $this->selectedTerm)
            ->get()
            ->toArray();

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

        // Digital signature QR data
        $qrData = "Yeschool Programme Signature\nCode: $uniqueCode\nEtablissement: $schoolName\nEdition: " . date('d/m/Y H:i') . "\nCertifie Conforme.";

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
            'courses' => $coursesList,
            'className' => $schoolClass->name . ' (' . $schoolClass->level . ')',
            'term' => $this->selectedTerm,
            'title' => "Programme de Cours - Classe de " . $schoolClass->name,
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.course-program', $data)->setPaper('a4', 'portrait');
        
        // Configure options directly on Dompdf
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'programme_cours_' . $uniqueCode . '.pdf');
    }
}
