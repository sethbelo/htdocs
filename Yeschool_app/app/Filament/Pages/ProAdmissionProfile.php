<?php

namespace App\Filament\Pages;

use App\Models\Admission;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\FeeType;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ProAdmissionProfile extends Page
{
    use WithFileUploads;

    protected static ?string $title = 'Profil Candidat';
    protected static ?string $navigationLabel = 'Profil Candidat';
    protected static ?string $navigationGroup = 'Scolarité';
    protected static bool $shouldRegisterNavigation = false; // We don't want it in the sidebar directly
    protected static ?string $slug = 'admissions/{record}';

    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-admission-profile';

    public $record;
    public Admission $admission;

    // Enrollment inputs
    public $isEnrollmentModalOpen = false;
    public $enroll_class_id;
    public $enroll_academic_year_id;
    public $enroll_matricule;
    public $enroll_avatar_upload;
    public $enroll_avatar_webcam;

    // View data arrays
    public $schoolClasses = [];
    public $academicYears = [];

    // Receipt details
    public $showReceipt = false;
    public $shouldShowReceipt = true;
    public $receiptData = [];

    public function mount($record)
    {
        $this->record = $record;
        $this->admission = Admission::findOrFail($record);
        
        $this->schoolClasses = SchoolClass::orderBy('name')->get()->toArray();
        $this->academicYears = AcademicYear::orderByDesc('start_date')->get()->toArray();

        // Pre-fill fields
        if (count($this->academicYears) > 0) {
            $this->enroll_academic_year_id = $this->academicYears[0]['id'];
        }
        
        // Match requested class if possible
        $requested = strtolower($this->admission->requested_class);
        foreach ($this->schoolClasses as $class) {
            if (strtolower($class['name']) === $requested) {
                $this->enroll_class_id = $class['id'];
                break;
            }
        }
        if (!$this->enroll_class_id && count($this->schoolClasses) > 0) {
            $this->enroll_class_id = $this->schoolClasses[0]['id'];
        }

        $this->generateMatricule();
    }

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

    public function setStatus($status)
    {
        $this->admission->status = $status;
        $this->admission->save();
        session()->flash('success', 'Statut mis à jour avec succès.');
    }

    public function openEnrollmentModal()
    {
        $this->generateMatricule();
        $this->isEnrollmentModalOpen = true;
    }

    public function confirmEnrollment($showReceipt = true)
    {
        $this->shouldShowReceipt = $showReceipt;
        $this->validate([
            'enroll_class_id' => 'required|exists:school_classes,id',
            'enroll_academic_year_id' => 'required|exists:academic_years,id',
            'enroll_matricule' => 'required|string|unique:students,matricule',
            'enroll_avatar_upload' => 'nullable|image|max:1024', // 1MB Max
            'enroll_avatar_webcam' => 'nullable|string',
        ]);

        // Process Avatar
        $avatarPath = null;
        if ($this->enroll_avatar_webcam) {
            // Webcam Base64 capture
            $imgData = $this->enroll_avatar_webcam;
            $imgData = str_replace('data:image/jpeg;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $data = base64_decode($imgData);
            $filename = 'avatar_' . uniqid() . '.jpg';
            
            // Put in public avatars folder
            Storage::put('public/avatars/' . $filename, $data);
            $avatarPath = 'avatars/' . $filename;
        } elseif ($this->enroll_avatar_upload) {
            // Uploaded file
            $avatarPath = $this->enroll_avatar_upload->store('avatars', 'public');
        }

        // 1. Create Student
        $student = Student::create([
            'matricule' => $this->enroll_matricule,
            'first_name' => $this->admission->first_name,
            'last_name' => $this->admission->last_name,
            'gender' => $this->admission->gender,
            'date_of_birth' => $this->admission->date_of_birth,
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

        // 3. Find or Create FeeType for Inscription
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

        // 4. Record Payment in Finance
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
        $this->admission->status = 'Inscrit';
        $this->admission->save();

        // 6. Generate Receipt data
        $this->receiptData = [
            'receipt_no' => 'REC-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
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

        $this->isEnrollmentModalOpen = false;
        $this->showReceipt = $this->shouldShowReceipt;

        session()->flash('success', 'Admission confirmée ! L\'élève a été inscrit avec succès.');
    }

    public function closeReceipt()
    {
        $this->showReceipt = false;
        return redirect()->to(\App\Filament\Pages\ProAdmissions::getUrl());
    }
}
