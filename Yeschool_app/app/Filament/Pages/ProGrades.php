<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Grade;
use Illuminate\Support\Facades\Auth;

class ProGrades extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-grades';
    protected static ?string $title = 'Notes & Bulletins';

    // Tabs
    public $activeTab = 'saisie'; // 'saisie', 'bulletins', or 'classroom'

    // Data lists
    public $schoolClasses = [];
    public $subjects = [];
    public $studentsList = [];

    // Classroom Tab Properties
    public $classroomAssessments = [];
    public $selectedAssessmentId = null;
    public $isAssessmentModalOpen = false;
    public $isComposeQuestionsModalOpen = false;
    public $isGradingModalOpen = false;

    // Assessment Form Inputs
    public $assessment_id = null;
    public $assessment_title = '';
    public $assessment_type = 'Devoir';
    public $assessment_max_score = 20.0;
    public $assessment_due_date = '';
    public $assessment_instructions = '';

    // Question Form Inputs
    public $assessment_questions = [];
    public $question_type = 'mcq';
    public $question_text = '';
    public $question_options = ['', '', '', ''];
    public $question_correct = '';
    public $question_image = '';

    // Grading Form Inputs
    public $selectedSubmissionId = null;
    public $grading_student_name = '';
    public $grading_answers = [];
    public $grading_score = null;
    public $grading_comments = '';
    
    // Submissions
    public $activeSubmissions = [];

    // Filters for Saisie
    public $selectedClassId = null;
    public $selectedSubjectId = null;
    public $selectedTerm = '1ère Période';

    // Form inputs for bulk entry
    public $gradesInput = []; // [student_id => ['score' => x, 'comments' => y]]
    public $currentMax = 10.0; // Current maximum score for Saisie input field validation

    // For Bulletins Tab
    public $selectedStudentId = null;
    public $bulletinData = [];

    public function mount()
    {
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if ($teacher) {
                $assignedClassIds = \App\Models\Course::where('teacher_id', $teacher->id)->pluck('school_class_id')->unique()->toArray();
                $this->schoolClasses = SchoolClass::whereIn('id', $assignedClassIds)->orderBy('level')->orderBy('name')->get()->toArray();
                
                if (!empty($this->schoolClasses)) {
                    $this->selectedClassId = $this->schoolClasses[0]['id'];
                    $assignedSubjectIds = \App\Models\Course::where('teacher_id', $teacher->id)
                        ->where('school_class_id', $this->selectedClassId)
                        ->pluck('subject_id')
                        ->unique()
                        ->toArray();
                    $this->subjects = Subject::whereIn('id', $assignedSubjectIds)->orderBy('name')->get()->toArray();
                } else {
                    $this->subjects = [];
                }
            } else {
                $this->schoolClasses = [];
                $this->subjects = [];
            }
        } else {
            $this->schoolClasses = SchoolClass::orderBy('level')->orderBy('name')->get()->toArray();
            $this->subjects = Subject::orderBy('name')->get()->toArray();
        }

        if (!empty($this->schoolClasses) && !$this->selectedClassId) {
            $this->selectedClassId = $this->schoolClasses[0]['id'];
        }
        if (!empty($this->subjects) && !$this->selectedSubjectId) {
            $this->selectedSubjectId = $this->subjects[0]['id'];
        }

        $this->loadStudentsForSaisie();
    }

    public function updatedSelectedClassId()
    {
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if ($teacher) {
                $assignedSubjectIds = \App\Models\Course::where('teacher_id', $teacher->id)
                    ->where('school_class_id', $this->selectedClassId)
                    ->pluck('subject_id')
                    ->unique()
                    ->toArray();
                $this->subjects = Subject::whereIn('id', $assignedSubjectIds)->orderBy('name')->get()->toArray();
                
                // Auto-select first subject if current one is not assigned
                if (!empty($this->subjects)) {
                    if (!in_array($this->selectedSubjectId, array_column($this->subjects, 'id'))) {
                        $this->selectedSubjectId = $this->subjects[0]['id'];
                    }
                } else {
                    $this->selectedSubjectId = null;
                }
            }
        }
        $this->loadStudentsForSaisie();
        if ($this->activeTab === 'bulletins' && !empty($this->studentsList)) {
            $this->selectedStudentId = $this->studentsList[0]['id'];
            $this->loadBulletin();
        }
    }

    public function updatedSelectedSubjectId()
    {
        $this->loadStudentsForSaisie();
    }

    public function updatedSelectedTerm()
    {
        $this->loadStudentsForSaisie();
        if ($this->activeTab === 'bulletins') {
            $this->loadBulletin();
        }
    }

    public function updatedSelectedStudentId()
    {
        $this->loadBulletin();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'bulletins') {
            if (empty($this->selectedStudentId) && !empty($this->studentsList)) {
                $this->selectedStudentId = $this->studentsList[0]['id'];
            }
            $this->loadBulletin();
        }
    }

    public function loadStudentsForSaisie()
    {
        if (!$this->selectedClassId || !$this->selectedSubjectId) {
            $this->studentsList = [];
            return;
        }

        // Verify if teacher is allowed to view this class/subject combination
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if (!$teacher) {
                $this->studentsList = [];
                return;
            }
            $hasCourse = \App\Models\Course::where('teacher_id', $teacher->id)
                ->where('school_class_id', $this->selectedClassId)
                ->where('subject_id', $this->selectedSubjectId)
                ->exists();
            if (!$hasCourse) {
                $this->studentsList = [];
                return;
            }
        }

        $this->studentsList = Student::where('school_class_id', $this->selectedClassId)
            ->where('status', 'Active')
            ->orderBy('last_name')
            ->get()->toArray();

        $existingGrades = Grade::whereIn('student_id', array_column($this->studentsList, 'id'))
            ->where('subject_id', $this->selectedSubjectId)
            ->where('term', $this->selectedTerm)
            ->get()->keyBy('student_id');

        // Calculate dynamic maximum score for this selection
        $course = \App\Models\Course::where('school_class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->first();
        $baseMax = $course ? $course->max_score : 10.0;
        $isExam = str_contains($this->selectedTerm, 'Examen');
        $this->currentMax = $isExam ? ($baseMax * 2) : $baseMax;

        $this->gradesInput = [];
        foreach ($this->studentsList as $student) {
            $g = $existingGrades->get($student['id']);
            $this->gradesInput[$student['id']] = [
                'score' => $g ? $g->score : '',
                'comments' => $g ? $g->comments : ''
            ];
        }

        $this->loadClassroomData();
    }

    public function saveGrades()
    {
        if (!$this->selectedClassId || !$this->selectedSubjectId) return;

        // Verify if teacher is allowed to save grades for this class/subject combination
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if (!$teacher) {
                abort(403, 'Accès non autorisé.');
            }
            $hasCourse = \App\Models\Course::where('teacher_id', $teacher->id)
                ->where('school_class_id', $this->selectedClassId)
                ->where('subject_id', $this->selectedSubjectId)
                ->exists();
            if (!$hasCourse) {
                abort(403, 'Accès non autorisé.');
            }
        }

        // Fetch course for max score
        $course = \App\Models\Course::where('school_class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId)
            ->first();
        $baseMax = $course ? $course->max_score : 10.0;
        
        $isExam = str_contains($this->selectedTerm, 'Examen');
        $maxScore = $isExam ? ($baseMax * 2) : $baseMax;

        foreach ($this->gradesInput as $studentId => $data) {
            if ($data['score'] !== '' && $data['score'] !== null) {
                Grade::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $this->selectedSubjectId,
                        'term' => $this->selectedTerm
                    ],
                    [
                        'score' => $data['score'],
                        'max_score' => $maxScore,
                        'comments' => $data['comments']
                    ]
                );
            }
        }

        session()->flash('success', 'Les notes ont été enregistrées avec succès.');
    }

    public function loadBulletin()
    {
        if (!$this->selectedStudentId) {
            $this->bulletinData = [];
            return;
        }

        $student = Student::with('schoolClass')->find($this->selectedStudentId);
        if (!$student) {
            $this->bulletinData = [];
            return;
        }

        // Fetch all course configurations for the class
        $courses = \App\Models\Course::where('school_class_id', $student->school_class_id)
            ->get()
            ->keyBy('subject_id');

        // Fetch all grades for this student
        $studentGrades = Grade::where('student_id', $student->id)->get()->groupBy('subject_id');

        // Fetch all subjects (filtered if user is Enseignant)
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : 0;
            $assignedSubjectIds = \App\Models\Course::where('teacher_id', $teacherId)
                ->where('school_class_id', $student->school_class_id)
                ->pluck('subject_id')
                ->unique()
                ->toArray();
            $allSubjects = Subject::whereIn('id', $assignedSubjectIds)->orderBy('name')->get();
        } else {
            $allSubjects = Subject::orderBy('name')->get();
        }

        // RDC Columns
        $columns = ['1P', '2P', 'Ex1', 'Tot1', '3P', '4P', 'Ex2', 'Tot2', 'TG'];

        // Totals per column
        $colStudentTotals = array_fill_keys($columns, 0.0);
        $colMaxTotals = array_fill_keys($columns, 0.0);

        $lines = [];
        foreach ($allSubjects as $subj) {
            $course = $courses->get($subj->id);
            $maxP = $course ? $course->max_score : 10.0;
            $maxEx = $maxP * 2;
            $maxSem = $maxP * 4;
            $maxTg = $maxP * 8;

            $grades = $studentGrades->get($subj->id) ?? collect();

            $p1 = $grades->where('term', '1ère Période')->first()?->score;
            $p2 = $grades->where('term', '2ème Période')->first()?->score;
            $ex1 = $grades->where('term', 'Examen 1er Semestre')->first()?->score;

            // Semester 1 calculation
            $tot1 = null;
            if ($p1 !== null || $p2 !== null || $ex1 !== null) {
                $tot1 = ($p1 ?? 0.0) + ($p2 ?? 0.0) + ($ex1 ?? 0.0);
            }

            $p3 = $grades->where('term', '3ème Période')->first()?->score;
            $p4 = $grades->where('term', '4ème Période')->first()?->score;
            $ex2 = $grades->where('term', 'Examen 2ème Semestre')->first()?->score;

            // Semester 2 calculation
            $tot2 = null;
            if ($p3 !== null || $p4 !== null || $ex2 !== null) {
                $tot2 = ($p3 ?? 0.0) + ($p4 ?? 0.0) + ($ex2 ?? 0.0);
            }

            // Total General
            $tg = null;
            if ($tot1 !== null || $tot2 !== null) {
                $tg = ($tot1 ?? 0.0) + ($tot2 ?? 0.0);
            }

            // Accumulate totals for general metrics
            if ($p1 !== null) { $colStudentTotals['1P'] += $p1; $colMaxTotals['1P'] += $maxP; }
            if ($p2 !== null) { $colStudentTotals['2P'] += $p2; $colMaxTotals['2P'] += $maxP; }
            if ($ex1 !== null) { $colStudentTotals['Ex1'] += $ex1; $colMaxTotals['Ex1'] += $maxEx; }
            if ($tot1 !== null) { $colStudentTotals['Tot1'] += $tot1; $colMaxTotals['Tot1'] += $maxSem; }
            if ($p3 !== null) { $colStudentTotals['3P'] += $p3; $colMaxTotals['3P'] += $maxP; }
            if ($p4 !== null) { $colStudentTotals['4P'] += $p4; $colMaxTotals['4P'] += $maxP; }
            if ($ex2 !== null) { $colStudentTotals['Ex2'] += $ex2; $colMaxTotals['Ex2'] += $maxEx; }
            if ($tot2 !== null) { $colStudentTotals['Tot2'] += $tot2; $colMaxTotals['Tot2'] += $maxSem; }
            if ($tg !== null) { $colStudentTotals['TG'] += $tg; $colMaxTotals['TG'] += $maxTg; }

            $lines[] = [
                'subject' => $subj->name,
                'color' => $subj->color,
                'max_p' => $maxP,
                'max_ex' => $maxEx,
                'max_sem' => $maxSem,
                'max_tg' => $maxTg,
                'p1' => $p1,
                'p2' => $p2,
                'ex1' => $ex1,
                'tot1' => $tot1,
                'p3' => $p3,
                'p4' => $p4,
                'ex2' => $ex2,
                'tot2' => $tot2,
                'tg' => $tg,
            ];
        }

        // Calculate rankings (Place) for each column in the class
        $colRanks = array_fill_keys($columns, '-');
        
        if ($student->school_class_id) {
            $classStudents = Student::where('school_class_id', $student->school_class_id)
                ->where('status', 'Active')
                ->get();
            $studentCount = $classStudents->count();

            // Fetch all grades for all students in the class
            $allClassGrades = Grade::whereIn('student_id', $classStudents->pluck('id'))->get()->groupBy('student_id');

            foreach ($columns as $col) {
                $studentScores = [];
                foreach ($classStudents as $cs) {
                    $cGrades = $allClassGrades->get($cs->id) ?? collect();
                    $scoreSum = 0.0;
                    $hasScores = false;

                    foreach ($allSubjects as $subj) {
                        $course = $courses->get($subj->id);
                        $maxP = $course ? $course->max_score : 10.0;

                        if ($col === '1P') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', '1ère Période')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === '2P') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', '2ème Période')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === 'Ex1') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 1er Semestre')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === 'Tot1') {
                            $p1Val = $cGrades->where('subject_id', $subj->id)->where('term', '1ère Période')->first()?->score;
                            $p2Val = $cGrades->where('subject_id', $subj->id)->where('term', '2ème Période')->first()?->score;
                            $ex1Val = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 1er Semestre')->first()?->score;
                            if ($p1Val !== null || $p2Val !== null || $ex1Val !== null) {
                                $scoreSum += ($p1Val ?? 0.0) + ($p2Val ?? 0.0) + ($ex1Val ?? 0.0);
                                $hasScores = true;
                            }
                        } elseif ($col === '3P') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', '3ème Période')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === '4P') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', '4ème Période')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === 'Ex2') {
                            $v = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 2ème Semestre')->first()?->score;
                            if ($v !== null) { $scoreSum += $v; $hasScores = true; }
                        } elseif ($col === 'Tot2') {
                            $p3Val = $cGrades->where('subject_id', $subj->id)->where('term', '3ème Période')->first()?->score;
                            $p4Val = $cGrades->where('subject_id', $subj->id)->where('term', '4ème Période')->first()?->score;
                            $ex2Val = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 2ème Semestre')->first()?->score;
                            if ($p3Val !== null || $p4Val !== null || $ex2Val !== null) {
                                $scoreSum += ($p3Val ?? 0.0) + ($p4Val ?? 0.0) + ($ex2Val ?? 0.0);
                                $hasScores = true;
                            }
                        } elseif ($col === 'TG') {
                            $p1Val = $cGrades->where('subject_id', $subj->id)->where('term', '1ère Période')->first()?->score;
                            $p2Val = $cGrades->where('subject_id', $subj->id)->where('term', '2ème Période')->first()?->score;
                            $ex1Val = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 1er Semestre')->first()?->score;
                            $p3Val = $cGrades->where('subject_id', $subj->id)->where('term', '3ème Période')->first()?->score;
                            $p4Val = $cGrades->where('subject_id', $subj->id)->where('term', '4ème Période')->first()?->score;
                            $ex2Val = $cGrades->where('subject_id', $subj->id)->where('term', 'Examen 2ème Semestre')->first()?->score;
                            if ($p1Val !== null || $p2Val !== null || $ex1Val !== null || $p3Val !== null || $p4Val !== null || $ex2Val !== null) {
                                $scoreSum += ($p1Val ?? 0.0) + ($p2Val ?? 0.0) + ($ex1Val ?? 0.0) + ($p3Val ?? 0.0) + ($p4Val ?? 0.0) + ($ex2Val ?? 0.0);
                                $hasScores = true;
                            }
                        }
                    }

                    if ($hasScores) {
                        $studentScores[$cs->id] = $scoreSum;
                    }
                }

                // Find rank
                if (isset($studentScores[$student->id])) {
                    arsort($studentScores);
                    $rank = 1;
                    foreach ($studentScores as $sId => $score) {
                        if ($sId == $student->id) {
                            $colRanks[$col] = $rank;
                            break;
                        }
                        $rank++;
                    }
                }
            }
        } else {
            $studentCount = 0;
        }

        // Percentage per column
        $colPercentages = [];
        foreach ($columns as $col) {
            $maxT = $colMaxTotals[$col];
            $colPercentages[$col] = $maxT > 0 ? round(($colStudentTotals[$col] / $maxT) * 100, 1) : null;
        }

        $this->bulletinData = [
            'student' => $student,
            'lines' => $lines,
            'colStudentTotals' => $colStudentTotals,
            'colMaxTotals' => $colMaxTotals,
            'colPercentages' => $colPercentages,
            'colRanks' => $colRanks,
            'studentCount' => $studentCount,
            'school_name' => \App\Models\Setting::get('school_name', 'Yeschool'),
            'school_address' => \App\Models\Setting::get('school_address', '123 Etablissement, RDC'),
            'school_phone' => \App\Models\Setting::get('school_phone', '+243 81 000 0000'),
            'school_email' => \App\Models\Setting::get('school_email', 'contact@yeschool.com'),
            'school_province' => \App\Models\Setting::get('school_province', 'Kinshasa'),
            'school_ville' => \App\Models\Setting::get('school_ville', 'Kinshasa'),
            'school_commune' => \App\Models\Setting::get('school_commune', 'Gombe'),
            'school_code_ecole' => \App\Models\Setting::get('school_code_ecole', '120409'),
        ];
    }

    public function loadClassroomData()
    {
        if (!$this->selectedClassId || !$this->selectedSubjectId) {
            $this->classroomAssessments = [];
            return;
        }

        $query = \App\Models\ClassroomAssessment::where('school_class_id', $this->selectedClassId)
            ->where('subject_id', $this->selectedSubjectId);

        // Enseignant restriction
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } else {
                $this->classroomAssessments = [];
                return;
            }
        }

        $this->classroomAssessments = $query->orderByDesc('created_at')->get()->toArray();
    }

    public function openAssessmentModal($assessmentId = null)
    {
        $this->assessment_id = $assessmentId;
        if ($assessmentId) {
            $assessment = \App\Models\ClassroomAssessment::findOrFail($assessmentId);
            $this->assessment_title = $assessment->title;
            $this->assessment_type = $assessment->type;
            $this->assessment_max_score = $assessment->max_score;
            $this->assessment_due_date = $assessment->due_date ? $assessment->due_date->format('Y-m-d\TH:i') : '';
            $this->assessment_instructions = $assessment->instructions;
        } else {
            $this->reset(['assessment_title', 'assessment_instructions']);
            $this->assessment_type = 'Devoir';
            $this->assessment_max_score = 20.0;
            $this->assessment_due_date = date('Y-m-d\T18:00');
        }
        $this->isAssessmentModalOpen = true;
    }

    public function saveAssessment()
    {
        if (!$this->selectedClassId || !$this->selectedSubjectId) {
            session()->flash('error', 'Veuillez sélectionner une classe et une matière avant d\'enregistrer.');
            return;
        }

        $this->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|in:Devoir,Travail,Examen',
            'assessment_max_score' => 'required|numeric|min:1',
            'assessment_due_date' => 'nullable'
        ]);

        $teacherId = null;
        if (Auth::user()->hasRole('Enseignant')) {
            $teacher = \App\Models\Teacher::where('email', Auth::user()->email)->first();
            $teacherId = $teacher ? $teacher->id : null;
        } else {
            // Find active teacher for this class/subject combination
            $course = \App\Models\Course::where('school_class_id', $this->selectedClassId)
                ->where('subject_id', $this->selectedSubjectId)
                ->first();
            $teacherId = $course ? $course->teacher_id : \App\Models\Teacher::first()?->id;
        }

        if (!$teacherId) {
            session()->flash('error', 'Aucun enseignant associé à ce cours.');
            return;
        }

        $academicYear = \App\Models\AcademicYear::where('is_active', true)->first() ?? \App\Models\AcademicYear::first();

        $assessment = \App\Models\ClassroomAssessment::updateOrCreate([
            'id' => $this->assessment_id
        ], [
            'school_class_id' => $this->selectedClassId,
            'subject_id' => $this->selectedSubjectId,
            'teacher_id' => $teacherId,
            'academic_year_id' => $academicYear ? $academicYear->id : null,
            'title' => $this->assessment_title,
            'type' => $this->assessment_type,
            'max_score' => $this->assessment_max_score,
            'due_date' => $this->assessment_due_date ? \Illuminate\Support\Carbon::parse($this->assessment_due_date) : null,
            'instructions' => $this->assessment_instructions
        ]);

        // Sync submissions for active students in class
        $students = Student::where('school_class_id', $this->selectedClassId)->where('status', 'Active')->get();
        foreach ($students as $student) {
            \App\Models\ClassroomSubmission::firstOrCreate([
                'classroom_assessment_id' => $assessment->id,
                'student_id' => $student->id
            ], [
                'status' => 'Non remis',
                'answers' => []
            ]);
        }

        $this->isAssessmentModalOpen = false;
        $this->loadClassroomData();
        session()->flash('success', 'Évaluation enregistrée avec succès.');
    }

    public function deleteAssessment($assessmentId)
    {
        $assessment = \App\Models\ClassroomAssessment::findOrFail($assessmentId);
        $assessment->delete();
        $this->loadClassroomData();
        session()->flash('success', 'Évaluation supprimée.');
    }

    public function openComposeQuestionsModal($assessmentId)
    {
        $this->selectedAssessmentId = $assessmentId;
        $assessment = \App\Models\ClassroomAssessment::findOrFail($assessmentId);
        $this->assessment_questions = $assessment->questions ?: [];
        $this->reset(['question_text', 'question_correct', 'question_image']);
        $this->question_type = 'mcq';
        $this->question_options = ['', '', '', ''];
        $this->isComposeQuestionsModalOpen = true;
    }

    public function addQuestion()
    {
        $this->validate([
            'question_text' => 'required|string'
        ]);

        $newQ = [
            'id' => 'q_' . uniqid(),
            'type' => $this->question_type,
            'question' => $this->question_text,
            'options' => $this->question_type === 'mcq' ? array_filter($this->question_options) : [],
            'correct_option' => $this->question_type === 'mcq' ? $this->question_correct : null,
            'image_url' => $this->question_image ?: null
        ];

        $this->assessment_questions[] = $newQ;
        $this->reset(['question_text', 'question_correct', 'question_image']);
        $this->question_options = ['', '', '', ''];
    }

    public function removeQuestion($index)
    {
        if (isset($this->assessment_questions[$index])) {
            unset($this->assessment_questions[$index]);
            $this->assessment_questions = array_values($this->assessment_questions);
        }
    }

    public function saveQuestions()
    {
        $assessment = \App\Models\ClassroomAssessment::findOrFail($this->selectedAssessmentId);
        $assessment->update([
            'questions' => $this->assessment_questions
        ]);
        $this->isComposeQuestionsModalOpen = false;
        $this->loadClassroomData();
        session()->flash('success', 'Questions de l\'évaluation mises à jour.');
    }

    public function viewAssessmentSubmissions($assessmentId)
    {
        $this->selectedAssessmentId = $assessmentId;
        $this->loadSubmissions();
    }

    public function loadSubmissions()
    {
        if (!$this->selectedAssessmentId) return;
        
        $this->activeSubmissions = \App\Models\ClassroomSubmission::with('student')
            ->where('classroom_assessment_id', $this->selectedAssessmentId)
            ->get()->toArray();
    }

    public function openGradingModal($submissionId)
    {
        $this->selectedSubmissionId = $submissionId;
        $sub = \App\Models\ClassroomSubmission::with(['student', 'assessment'])->findOrFail($submissionId);
        $this->grading_student_name = $sub->student->first_name . ' ' . $sub->student->last_name;
        
        // Prepare default grading answers if empty
        $questions = $sub->assessment->questions ?: [];
        $savedAnswers = $sub->answers ?: [];
        
        $this->grading_answers = [];
        foreach ($questions as $q) {
            $studentAns = '';
            foreach ($savedAnswers as $sa) {
                if ($sa['question_id'] === $q['id']) {
                    $studentAns = $sa['student_answer'];
                    break;
                }
            }
            $this->grading_answers[$q['id']] = $studentAns;
        }

        $this->grading_score = $sub->score;
        $this->grading_comments = $sub->teacher_comments;
        $this->isGradingModalOpen = true;
    }

    public function saveGrading()
    {
        $sub = \App\Models\ClassroomSubmission::with('assessment')->findOrFail($this->selectedSubmissionId);
        
        $this->validate([
            'grading_score' => 'nullable|numeric|min:0|max:' . $sub->assessment->max_score
        ]);

        $formattedAnswers = [];
        foreach ($this->grading_answers as $qId => $ans) {
            $formattedAnswers[] = [
                'question_id' => $qId,
                'student_answer' => $ans
            ];
        }

        $sub->update([
            'answers' => $formattedAnswers,
            'score' => $this->grading_score,
            'teacher_comments' => $this->grading_comments,
            'status' => $this->grading_score !== null ? 'Corrigé' : 'À corriger'
        ]);

        // Auto-propagate score to default grades table if corrected
        if ($sub->score !== null) {
            \App\Models\Grade::updateOrCreate([
                'student_id' => $sub->student_id,
                'subject_id' => $sub->assessment->subject_id,
                'term' => $this->selectedTerm
            ], [
                'score' => $sub->score,
                'max_score' => $sub->assessment->max_score,
                'comments' => $sub->teacher_comments ?: 'Classroom: ' . $sub->assessment->title
            ]);
        }

        $this->isGradingModalOpen = false;
        $this->loadSubmissions();
        $this->loadStudentsForSaisie(); // sync base grades too
        session()->flash('success', 'Copie corrigée et enregistrée.');
    }

    public function transmitToParent($submissionId, $method)
    {
        $sub = \App\Models\ClassroomSubmission::with(['student', 'assessment.subject'])->findOrFail($submissionId);
        
        // Simulating the email/whatsapp transmission message
        $parentName = $sub->student->parent_name ?: 'Parent d\'élève';
        $parentPhone = $sub->student->parent_phone ?: '+243 000 000 000';
        $parentEmail = $sub->student->parent_email ?: 'parent@example.com';
        
        $assessmentTitle = $sub->assessment->title;
        $scoreText = $sub->score !== null ? "{$sub->score} / {$sub->assessment->max_score}" : 'Non noté';

        if ($method === 'whatsapp') {
            session()->flash('success', "Copie Classroom envoyée par WhatsApp à {$parentName} ({$parentPhone}) : Évaluation : {$assessmentTitle} - Score : {$scoreText}");
        } else {
            session()->flash('success', "Copie Classroom envoyée par Email à {$parentEmail} : Évaluation : {$assessmentTitle} - Score : {$scoreText}");
        }
    }

    public function exportSubmissionPdf($submissionId)
    {
        $sub = \App\Models\ClassroomSubmission::with(['student.schoolClass', 'assessment.subject', 'assessment.teacher'])->findOrFail($submissionId);
        
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
        $uniqueCode = strtoupper(substr(md5($submissionId . time()), 0, 10));

        // Digital signature QR data
        $qrData = "Yeschool Devoir Classroom\nCode: $uniqueCode\nEtablissement: $schoolName\nStudent: " . $sub->student->first_name . " " . $sub->student->last_name . "\nNote: " . ($sub->score !== null ? $sub->score : 'A corriger') . "/" . $sub->assessment->max_score . "\nCertifie Conforme.";

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
                    "timeout" => 3,
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
            'submission' => $sub->toArray(),
            'assessment' => $sub->assessment->toArray(),
            'student' => $sub->student->toArray(),
            'subject' => $sub->assessment->subject->toArray(),
            'teacher' => $sub->assessment->teacher->toArray(),
            'title' => "Copie d'Évaluation : " . $sub->assessment->title,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolPhone' => $schoolPhone,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'uniqueCode' => $uniqueCode,
            'qrCodeBase64' => $qrCodeBase64,
            'qrData' => $qrData,
            'footer' => "Généré par Yeschool Classroom - " . date('d/m/Y H:i')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.classroom-copy', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);

        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'evaluation_' . $uniqueCode . '.pdf');
    }
}
