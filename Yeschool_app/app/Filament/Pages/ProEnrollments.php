<?php

namespace App\Filament\Pages;

use App\Models\Admission;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\FeeType;
use Filament\Pages\Page;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ProEnrollments extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Inscriptions & Réinscriptions';
    protected static ?string $navigationLabel = 'Inscriptions';
    protected static ?string $navigationGroup = 'Scolarité';
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $slug = 'inscriptions';

    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-enrollments';

    public $activeTab = 'inscription'; // 'inscription', 'reinscription'

    // Dropdowns data
    public $schoolClasses = [];
    public $academicYears = [];
    public $acceptedAdmissions = [];

    // Shared Receipt
    public $showReceipt = false;
    public $shouldShowReceipt = true;
    public $receiptData = [];

    // --- TAB: INSCRIPTION ---
    public $admission_id;
    public $enroll_matricule;
    public $enroll_academic_year_id;
    public $enroll_class_id;
    public $enroll_avatar_upload;
    public $enroll_avatar_webcam;

    // --- TAB: REINSCRIPTION ---
    public $re_matricule;
    public $re_academic_year_id;
    public $re_class_id;
    public $re_student_id;
    public $re_student_name = '';
    public $re_student_current_class = '';
    public $re_student_found = false;

    public function mount()
    {
        $this->schoolClasses = SchoolClass::orderBy('name')->get()->toArray();
        $this->academicYears = AcademicYear::orderByDesc('start_date')->get()->toArray();
        $this->loadAcceptedAdmissions();

        // Default inputs
        if (count($this->academicYears) > 0) {
            $this->enroll_academic_year_id = $this->academicYears[0]['id'];
            $this->re_academic_year_id = $this->academicYears[0]['id'];
        }
        if (count($this->schoolClasses) > 0) {
            $this->enroll_class_id = $this->schoolClasses[0]['id'];
            $this->re_class_id = $this->schoolClasses[0]['id'];
        }

        $this->generateMatricule();
    }

    public function loadAcceptedAdmissions()
    {
        $this->acceptedAdmissions = Admission::where('status', 'Accepté')
            ->orderBy('last_name')
            ->get()
            ->toArray();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->showReceipt = false;
        $this->resetValidation();
    }

    // --- INSCRIPTION METHOD ---
    public function generateMatricule()
    {
        if (!$this->enroll_academic_year_id) {
            return;
        }

        $year = AcademicYear::find($this->enroll_academic_year_id);
        $yearName = $year ? $year->name : date('Y');
        $yearOnly = substr($yearName, 0, 4);
        if (!is_numeric($yearOnly)) {
            $yearOnly = date('Y');
        }

        do {
            $randomCode = rand(100000, 999999);
            $matricule = 'MAT-' . $randomCode . '-' . $yearOnly;
        } while (Student::where('matricule', $matricule)->exists());

        $this->enroll_matricule = $matricule;
    }

    public function updatedEnrollAcademicYearId()
    {
        $this->generateMatricule();
    }

    public function updatedAdmissionId($value)
    {
        $admission = Admission::find($value);
        if ($admission) {
            // Auto-match requested class if possible
            $requested = strtolower($admission->requested_class);
            foreach ($this->schoolClasses as $class) {
                if (strtolower($class['name']) === $requested) {
                    $this->enroll_class_id = $class['id'];
                    break;
                }
            }
        }
    }

    public function submitInscription($showReceipt = true)
    {
        $this->shouldShowReceipt = $showReceipt;
        $this->validate([
            'admission_id' => 'required|exists:admissions,id',
            'enroll_class_id' => 'required|exists:school_classes,id',
            'enroll_academic_year_id' => 'required|exists:academic_years,id',
            'enroll_matricule' => 'required|string|unique:students,matricule',
            'enroll_avatar_upload' => 'nullable|image|max:1024',
            'enroll_avatar_webcam' => 'nullable|string',
        ]);

        $admission = Admission::findOrFail($this->admission_id);

        // Process Avatar
        $avatarPath = null;
        if ($this->enroll_avatar_webcam) {
            $imgData = $this->enroll_avatar_webcam;
            $imgData = str_replace('data:image/jpeg;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $data = base64_decode($imgData);
            $filename = 'avatar_' . uniqid() . '.jpg';
            Storage::put('public/avatars/' . $filename, $data);
            $avatarPath = 'avatars/' . $filename;
        } elseif ($this->enroll_avatar_upload) {
            $avatarPath = $this->enroll_avatar_upload->store('avatars', 'public');
        }

        // 1. Create Student
        $student = Student::create([
            'matricule' => $this->enroll_matricule,
            'first_name' => $admission->first_name,
            'last_name' => $admission->last_name,
            'gender' => $admission->gender ?? 'M',
            'date_of_birth' => $admission->date_of_birth ?? date('Y-m-d'),
            'school_class_id' => $this->enroll_class_id,
            'status' => 'Actif',
            'avatar' => $avatarPath,
        ]);

        // 2. Create Enrollment
        Enrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->enroll_academic_year_id,
            'school_class_id' => $this->enroll_class_id,
            'type' => 'inscription',
        ]);

        // 3. Find or Create FeeType
        $feeType = FeeType::where('name', 'Frais d\'Inscription')->first();
        if (!$feeType) {
            $feeType = FeeType::create([
                'name' => 'Frais d\'Inscription',
                'amount' => 150.00,
                'currency' => 'USD',
                'exchange_rate' => 2500.00,
                'ohada_account_code' => '706',
            ]);
        }

        // 4. Create Payment
        $payment = Payment::create([
            'transaction_type' => 'Entrée',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'amount' => $feeType->amount,
            'currency' => $feeType->currency,
            'payment_date' => now(),
            'reference' => 'INS-' . $student->matricule,
            'description' => 'Inscription de l\'élève ' . $student->first_name . ' ' . $student->last_name,
            'ohada_account_code' => $feeType->ohada_account_code,
        ]);

        // 5. Update Admission Status
        $admission->status = 'Inscrit';
        $admission->save();

        // 6. Generate Receipt data
        $this->receiptData = [
            'receipt_no' => 'REC-INS-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
            'date' => now()->format('d/m/Y H:i'),
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'matricule' => $student->matricule,
            'class' => SchoolClass::find($this->enroll_class_id)?->name ?? 'Classe',
            'academic_year' => AcademicYear::find($this->enroll_academic_year_id)?->name ?? 'Année Scolaire',
            'fee_name' => $feeType->name,
            'amount' => $feeType->amount,
            'currency' => $feeType->currency,
            'amount_cdf' => $feeType->amount * $feeType->exchange_rate,
            'exchange_rate' => $feeType->exchange_rate,
            'reference' => $payment->reference,
        ];

        $this->showReceipt = $this->shouldShowReceipt;

        session()->flash('success', 'Inscription confirmée avec succès !');

        // Reset fields
        $this->reset(['admission_id', 'enroll_avatar_upload', 'enroll_avatar_webcam']);
        $this->loadAcceptedAdmissions();
        $this->generateMatricule();
    }

    // --- REINSCRIPTION METHOD ---
    public function updatedReMatricule($value)
    {
        $student = Student::where('matricule', trim($value))->first();
        if ($student) {
            $this->re_student_id = $student->id;
            $this->re_student_name = $student->first_name . ' ' . $student->last_name;
            $this->re_student_current_class = $student->schoolClass ? $student->schoolClass->name : 'Aucune';
            $this->re_student_found = true;
        } else {
            $this->re_student_id = null;
            $this->re_student_name = '';
            $this->re_student_current_class = '';
            $this->re_student_found = false;
        }
    }

    public function submitReinscription($showReceipt = true)
    {
        $this->shouldShowReceipt = $showReceipt;
        $this->validate([
            're_matricule' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $student = Student::where('matricule', trim($value))->first();
                    if (!$student) {
                        $fail("Le matricule n'existe pas.");
                    }
                }
            ],
            're_class_id' => 'required|exists:school_classes,id',
            're_academic_year_id' => [
                'required',
                'exists:academic_years,id',
                function ($attribute, $value, $fail) {
                    if ($this->re_student_id) {
                        $alreadyEnrolled = Enrollment::where('student_id', $this->re_student_id)
                            ->where('academic_year_id', $value)
                            ->exists();
                        if ($alreadyEnrolled) {
                            $fail("Cet élève est déjà réinscrit pour cette année scolaire.");
                        }
                    }
                }
            ],
        ]);

        $student = Student::findOrFail($this->re_student_id);

        // 1. Create Enrollment record
        Enrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $this->re_class_id,
            'academic_year_id' => $this->re_academic_year_id,
            'type' => 'reinscription',
        ]);

        // 2. Update Student current class
        $student->update([
            'school_class_id' => $this->re_class_id,
        ]);

        // 3. Find or Create FeeType
        $feeType = FeeType::where('name', 'Frais de Réinscription')->first();
        if (!$feeType) {
            $feeType = FeeType::create([
                'name' => 'Frais de Réinscription',
                'amount' => 80.00,
                'currency' => 'USD',
                'exchange_rate' => 2500.00,
                'ohada_account_code' => '706',
            ]);
        }

        // 4. Create Payment
        $payment = Payment::create([
            'transaction_type' => 'Entrée',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'amount' => $feeType->amount,
            'currency' => $feeType->currency,
            'payment_date' => now(),
            'reference' => 'REI-' . $student->matricule . '-' . time(),
            'description' => 'Réinscription de l\'élève ' . $student->first_name . ' ' . $student->last_name,
            'ohada_account_code' => $feeType->ohada_account_code,
        ]);

        // 5. Generate Receipt data
        $this->receiptData = [
            'receipt_no' => 'REC-REI-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
            'date' => now()->format('d/m/Y H:i'),
            'student_name' => $student->first_name . ' ' . $student->last_name,
            'matricule' => $student->matricule,
            'class' => SchoolClass::find($this->re_class_id)?->name ?? 'Classe',
            'academic_year' => AcademicYear::find($this->re_academic_year_id)?->name ?? 'Année Scolaire',
            'fee_name' => $feeType->name,
            'amount' => $feeType->amount,
            'currency' => $feeType->currency,
            'amount_cdf' => $feeType->amount * $feeType->exchange_rate,
            'exchange_rate' => $feeType->exchange_rate,
            'reference' => $payment->reference,
        ];

        $this->showReceipt = $this->shouldShowReceipt;

        session()->flash('success', 'Réinscription effectuée avec succès !');

        // Reset fields
        $this->reset(['re_matricule', 're_student_id', 're_student_name', 're_student_current_class', 're_student_found']);
    }

    public function closeReceipt()
    {
        $this->showReceipt = false;
    }
}
