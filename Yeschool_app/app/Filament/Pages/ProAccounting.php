<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Payment;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProAccounting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-accounting';
    protected static ?string $title = 'Finances & Comptabilité';

    public $activeTab = 'scolarite'; // 'scolarite', 'salaires', 'situation_eleves', 'ohada'

    // Data
    public $payments = [];
    public $feeTypes = [];
    public $students = [];
    public $teachers = [];
    public $studentsSituation = [];
    
    // Search queries
    public $searchSituation = '';
    
    // OHADA Bilan
    public $totalRevenus = 0;
    public $totalCharges = 0;

    // Form Modals
    public $isPaymentModalOpen = false;
    public $isDerogationModalOpen = false;
    public $isCardPaymentModalOpen = false;

    // Form Fields
    public $form_transaction_type = 'Entrée';
    public $form_class_id;
    public $form_student_id;
    public $form_teacher_id;
    public $form_fee_type_id;
    public $form_amount;
    public $form_payment_method = 'Cash';
    public $form_payment_date;
    public $form_reference;
    public $form_description;
    public $form_ohada_code;

    // Search query inside the form
    public $searchFormStudent = '';
    public $schoolClasses = [];

    // Track Selected Student & Fee Type details for payment form
    public $form_fixed_amount_usd = 0.00;
    public $form_fixed_amount_cdf = 0.00;
    public $form_paid_amount_usd = 0.00;
    public $form_paid_amount_cdf = 0.00;
    public $form_remaining_amount_usd = 0.00;
    public $form_remaining_amount_cdf = 0.00;
    public $form_exchange_rate = 2500;

    // Décaissement specific fields
    public $form_beneficiary_type = 'Enseignant'; // 'Enseignant', 'Autre'
    public $form_beneficiary_name = '';
    public $form_motif = 'Autre'; // 'Achat', 'Transport', 'Salaires', 'Loyer', 'Maintenance', 'Autre'

    // Sortie totals
    public $totalApprovedSorties = 0;
    public $totalPendingSorties = 0;

    // Selected Student for Modals
    public $selectedStudent;
    public $selectedStudentIdForModal;

    // Card Form Fields
    public $card_holder_name;
    public $card_number;
    public $card_expiry;
    public $card_cvv;
    public $card_amount;
    public $card_fee_type_id;

    // Derogation Form Fields
    public $derogation_is_teacher_child = false;
    public $derogation_has_derogation = false;
    public $derogation_notes;
    public $derogation_advance_balance = 0.00;

    // OHADA Date Filter Modal
    public $ohada_start_date;
    public $ohada_end_date;
    public $isOhadaFilterModalOpen = false;

    // Cashier Inflow Report (Rapport de Caisse) Filters & Data
    public $rapport_start_date;
    public $rapport_end_date;
    public $rapport_class_id;
    public $rapport_fee_type_id;
    public $rapport_payment_method;
    public $isRapportFilterModalOpen = false;
    public $rapportPayments = [];
    public $academicYears = [];

    // Cashier Aggregated Totals
    public $totalRapportJour = 0;
    public $totalRapportMois = 0;
    public $totalRapportAnnee = 0;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'Comptable', 'Caissier', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé. Réservé au service financier.');

        $this->feeTypes = FeeType::all()->toArray();
        $this->students = Student::with(['schoolClass'])->orderBy('last_name')->get()->toArray();
        $this->teachers = Teacher::orderBy('last_name')->get()->toArray();
        $this->schoolClasses = \App\Models\SchoolClass::orderBy('name')->get()->toArray();
        $this->academicYears = \App\Models\AcademicYear::orderByDesc('start_date')->get()->toArray();

        $this->ohada_start_date = date('Y-m-01'); // First day of current month
        $this->ohada_end_date = date('Y-m-t');   // Last day of current month

        $this->rapport_start_date = date('Y-m-01'); // First day of current month
        $this->rapport_end_date = date('Y-m-d');    // Today

        $this->loadData();
    }

    public function switchTab($tab)
    {
        if ($tab === 'salaires' || $tab === 'ohada') {
            abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé.');
        }
        $this->activeTab = $tab;
        $this->loadData();
    }

    public function updatedSearchSituation()
    {
        $this->loadData();
    }

    public function loadData()
    {
        if ($this->activeTab === 'scolarite') {
            $this->payments = Payment::with(['student', 'feeType'])
                ->where('transaction_type', 'Entrée')
                ->orderByDesc('payment_date')
                ->get()->toArray();
        } elseif ($this->activeTab === 'salaires') {
            $this->payments = Payment::with(['teacher'])
                ->where('transaction_type', 'Sortie')
                ->orderByDesc('payment_date')
                ->get()->toArray();
            
            // Calculate dynamic totals for Sorties (décaissements)
            $this->totalApprovedSorties = Payment::where('transaction_type', 'Sortie')->where('status', 'approved')->sum('amount');
            $this->totalPendingSorties = Payment::where('transaction_type', 'Sortie')->where('status', 'pending')->sum('amount');
        } elseif ($this->activeTab === 'ohada') {
            $queryRev = Payment::where('transaction_type', 'Entrée')->where('status', 'approved');
            $queryChg = Payment::where('transaction_type', 'Sortie')->where('status', 'approved');
            
            if ($this->ohada_start_date) {
                $queryRev->whereDate('payment_date', '>=', $this->ohada_start_date);
                $queryChg->whereDate('payment_date', '>=', $this->ohada_start_date);
            }
            if ($this->ohada_end_date) {
                $queryRev->whereDate('payment_date', '<=', $this->ohada_end_date);
                $queryChg->whereDate('payment_date', '<=', $this->ohada_end_date);
            }
            
            $this->totalRevenus = $queryRev->sum('amount');
            $this->totalCharges = $queryChg->sum('amount');
        } elseif ($this->activeTab === 'situation_eleves') {
            $this->loadSituationData();
        } elseif ($this->activeTab === 'rapport_caisse') {
            // Aggregated Inflow totals (Jour, Mois, Année)
            $this->totalRapportJour = Payment::where('transaction_type', 'Entrée')
                ->whereDate('payment_date', Carbon::today())
                ->sum('amount');

            $this->totalRapportMois = Payment::where('transaction_type', 'Entrée')
                ->whereMonth('payment_date', Carbon::today()->month)
                ->whereYear('payment_date', Carbon::today()->year)
                ->sum('amount');

            $this->totalRapportAnnee = Payment::where('transaction_type', 'Entrée')
                ->whereYear('payment_date', Carbon::today()->year)
                ->sum('amount');

            // Filtered Query
            $query = Payment::with(['student.schoolClass', 'feeType'])
                ->where('transaction_type', 'Entrée')
                ->orderByDesc('payment_date');

            if ($this->rapport_start_date) {
                $query->whereDate('payment_date', '>=', $this->rapport_start_date);
            }
            if ($this->rapport_end_date) {
                $query->whereDate('payment_date', '<=', $this->rapport_end_date);
            }
            if ($this->rapport_class_id) {
                $query->whereHas('student', function($q) {
                    $q->where('school_class_id', $this->rapport_class_id);
                });
            }
            if ($this->rapport_fee_type_id) {
                $query->where('fee_type_id', $this->rapport_fee_type_id);
            }
            if ($this->rapport_payment_method) {
                $query->where('payment_method', $this->rapport_payment_method);
            }

            $this->rapportPayments = $query->get()->toArray();
        }
    }

    public function loadSituationData()
    {
        $query = Student::with(['schoolClass']);
        if ($this->searchSituation) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->searchSituation . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchSituation . '%')
                  ->orWhere('matricule', 'like', '%' . $this->searchSituation . '%');
            });
        }
        $studentsList = $query->orderBy('last_name')->get();
        
        $feeTypes = FeeType::all();
        $this->studentsSituation = [];
        
        foreach ($studentsList as $s) {
            $tranches = [];
            $totalDueUsd = 0;
            $totalDueCdf = 0;
            $totalPaidUsd = 0;
            $totalPaidCdf = 0;
            $totalAdvancesUsd = 0;
            $totalAdvancesCdf = 0;
            
            foreach ($feeTypes as $ft) {
                $paidForTypeUsd = Payment::where('student_id', $s->id)
                    ->where('fee_type_id', $ft->id)
                    ->where('transaction_type', 'Entrée')
                    ->sum('amount');
                
                $dueUsd = $s->is_teacher_child ? 0.00 : $ft->amount;
                $dueCdf = $dueUsd * $ft->exchange_rate;
                
                $paidForTypeCdf = $paidForTypeUsd * $ft->exchange_rate;
                $remainingUsd = max(0, $dueUsd - $paidForTypeUsd);
                $remainingCdf = $remainingUsd * $ft->exchange_rate;
                
                $advanceUsd = max(0, $paidForTypeUsd - $dueUsd);
                $advanceCdf = $advanceUsd * $ft->exchange_rate;
                
                $trancheStatus = 'Non payé';
                if ($s->is_teacher_child) {
                    $trancheStatus = 'Exonéré';
                } elseif ($paidForTypeUsd > 0 && $paidForTypeUsd < $dueUsd) {
                    $trancheStatus = 'Acompte';
                } elseif ($paidForTypeUsd > 0 && $paidForTypeUsd == $dueUsd) {
                    $trancheStatus = 'Soldé';
                } elseif ($paidForTypeUsd > $dueUsd) {
                    $trancheStatus = 'Avance';
                }
                
                $tranches[] = [
                    'fee_type_id' => $ft->id,
                    'name' => $ft->name,
                    'fixed_amount_usd' => $dueUsd,
                    'fixed_amount_cdf' => $dueCdf,
                    'paid_amount_usd' => $paidForTypeUsd,
                    'paid_amount_cdf' => $paidForTypeCdf,
                    'remaining_amount_usd' => $remainingUsd,
                    'remaining_amount_cdf' => $remainingCdf,
                    'status' => $trancheStatus,
                    'exchange_rate' => $ft->exchange_rate
                ];
                
                $totalDueUsd += $dueUsd;
                $totalDueCdf += $dueCdf;
                $totalPaidUsd += $paidForTypeUsd;
                $totalPaidCdf += $paidForTypeCdf;
                $totalAdvancesUsd += $advanceUsd;
                $totalAdvancesCdf += $advanceCdf;
            }
            
            // Overall status
            $overallRemainingUsd = max(0, $totalDueUsd - $totalPaidUsd - $s->advance_balance);
            $overallRemainingCdf = max(0, $totalDueCdf - $totalPaidCdf - ($s->advance_balance * 2500.00));
            
            $displayAdvanceUsd = $totalAdvancesUsd + $s->advance_balance;
            $displayAdvanceCdf = $totalAdvancesCdf + ($s->advance_balance * 2500.00);
            
            $overallStatus = 'En retard';
            if ($s->is_teacher_child) {
                $overallStatus = 'Exonéré';
            } elseif ($overallRemainingUsd <= 0) {
                $overallStatus = 'En règle';
            } elseif ($s->has_derogation) {
                $overallStatus = 'Dérogation';
            }
            
            $this->studentsSituation[] = [
                'id' => $s->id,
                'name' => "{$s->first_name} {$s->last_name}",
                'matricule' => $s->matricule ?: 'N/A',
                'class_name' => $s->schoolClass?->name ?? 'N/A',
                'parent_name' => $s->parent_name ?? 'N/A',
                'parent_phone' => $s->parent_phone ?? '',
                'due_usd' => $totalDueUsd,
                'due_cdf' => $totalDueCdf,
                'paid_usd' => $totalPaidUsd,
                'paid_cdf' => $totalPaidCdf,
                'advance_usd' => $displayAdvanceUsd,
                'advance_cdf' => $displayAdvanceCdf,
                'remaining_usd' => $overallRemainingUsd,
                'remaining_cdf' => $overallRemainingCdf,
                'is_teacher_child' => $s->is_teacher_child,
                'has_derogation' => $s->has_derogation,
                'derogation_notes' => $s->derogation_notes,
                'status' => $overallStatus,
                'tranches' => $tranches
            ];
        }
    }

    public function openPaymentModalForStudent($studentId)
    {
        $this->openPaymentModal('Entrée');
        $student = Student::find($studentId);
        if ($student) {
            $this->form_class_id = $student->school_class_id;
            $this->form_student_id = $student->id;
            $this->updateFormAmountAndDetails();
        }
    }

    public function openPaymentModal($type)
    {
        if ($type === 'Sortie') {
            abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé aux décaissements.');
        }
        $this->resetValidation();
        $this->reset([
            'form_class_id', 'form_student_id', 'form_teacher_id', 'form_fee_type_id', 'form_amount', 'form_reference', 'form_description', 'form_ohada_code',
            'form_fixed_amount_usd', 'form_fixed_amount_cdf', 'form_paid_amount_usd', 'form_paid_amount_cdf', 'form_remaining_amount_usd', 'form_remaining_amount_cdf',
            'searchFormStudent'
        ]);
        $this->form_transaction_type = $type;
        $this->form_payment_method = 'Cash';
        $this->form_payment_date = Carbon::now()->format('Y-m-d');
        
        if ($type === 'Entrée') {
            $this->form_ohada_code = '706'; // Services
        } else {
            $this->form_ohada_code = '661'; // Salaires
        }
        
        $this->isPaymentModalOpen = true;
    }

    public function updatedFormClassId()
    {
        $this->form_student_id = null;
        $this->reset([
            'form_fixed_amount_usd', 'form_fixed_amount_cdf', 'form_paid_amount_usd', 'form_paid_amount_cdf', 'form_remaining_amount_usd', 'form_remaining_amount_cdf'
        ]);
    }

    public function updatedFormStudentId()
    {
        $this->updateFormAmountAndDetails();
    }

    public function updatedFormFeeTypeId()
    {
        $this->updateFormAmountAndDetails();
    }

    public function updateFormAmountAndDetails()
    {
        if ($this->form_transaction_type === 'Entrée' && $this->form_student_id && $this->form_fee_type_id) {
            $student = Student::find($this->form_student_id);
            $feeType = FeeType::find($this->form_fee_type_id);
            
            if ($student && $feeType) {
                $this->form_exchange_rate = $feeType->exchange_rate;
                $dueUsd = $student->is_teacher_child ? 0.00 : $feeType->amount;
                $dueCdf = $dueUsd * $feeType->exchange_rate;
                
                $paidUsd = Payment::where('student_id', $student->id)
                    ->where('fee_type_id', $feeType->id)
                    ->where('transaction_type', 'Entrée')
                    ->sum('amount');
                $paidCdf = $paidUsd * $feeType->exchange_rate;
                
                $remainingUsd = max(0, $dueUsd - $paidUsd);
                $remainingCdf = $remainingUsd * $feeType->exchange_rate;
                
                $this->form_fixed_amount_usd = $dueUsd;
                $this->form_fixed_amount_cdf = $dueCdf;
                $this->form_paid_amount_usd = $paidUsd;
                $this->form_paid_amount_cdf = $paidCdf;
                $this->form_remaining_amount_usd = $remainingUsd;
                $this->form_remaining_amount_cdf = $remainingCdf;
                
                // Pre-fill the amount field with the remaining balance
                $this->form_amount = $remainingUsd;
            }
        } else {
            $this->reset(['form_fixed_amount_usd', 'form_fixed_amount_cdf', 'form_paid_amount_usd', 'form_paid_amount_cdf', 'form_remaining_amount_usd', 'form_remaining_amount_cdf']);
        }
    }

    public function savePayment()
    {
        if ($this->form_transaction_type === 'Sortie') {
            abort_if(Auth::user()->hasRole('Caissier'), 403, 'Accès non autorisé aux décaissements.');
        }
        $this->validate([
            'form_transaction_type' => 'required|in:Entrée,Sortie',
            'form_amount' => 'required|numeric|min:0',
            'form_payment_date' => 'required|date',
            'form_student_id' => 'required_if:form_transaction_type,Entrée',
            'form_teacher_id' => 'required_if:form_beneficiary_type,Enseignant',
            'form_beneficiary_name' => 'required_if:form_beneficiary_type,Autre',
        ]);

        $beneficiary = null;
        if ($this->form_transaction_type === 'Sortie') {
            $beneficiary = $this->form_beneficiary_type === 'Enseignant' 
                ? null 
                : $this->form_beneficiary_name;
        }

        $payment = Payment::create([
            'transaction_type' => $this->form_transaction_type,
            'student_id' => $this->form_student_id ?: null,
            'teacher_id' => ($this->form_transaction_type === 'Sortie' && $this->form_beneficiary_type === 'Enseignant') ? $this->form_teacher_id : null,
            'beneficiary_name' => $beneficiary,
            'motif' => $this->form_transaction_type === 'Sortie' ? $this->form_motif : null,
            'status' => $this->form_transaction_type === 'Sortie' ? 'pending' : 'approved',
            'fee_type_id' => $this->form_fee_type_id ?: null,
            'amount' => $this->form_amount,
            'payment_method' => $this->form_payment_method ?? 'Cash',
            'currency' => 'USD',
            'payment_date' => $this->form_payment_date,
            'reference' => $this->form_reference ?? 'REF-' . strtoupper(uniqid()),
            'description' => $this->form_description,
            'ohada_account_code' => $this->form_ohada_code,
        ]);

        // Auto calculate advance balance if payment is for Entrée and amount exceeds remaining balance due
        if ($this->form_transaction_type === 'Entrée' && $this->form_student_id) {
            $student = Student::find($this->form_student_id);
            if ($student) {
                $feeTypesSum = FeeType::sum('amount');
                $due = $student->is_teacher_child ? 0.00 : $feeTypesSum;
                $paid = Payment::where('student_id', $student->id)->where('transaction_type', 'Entrée')
                    ->where('id', '!=', $payment->id)->sum('amount');
                $currentRemaining = max(0, $due - $paid);
                
                if ($this->form_amount > $currentRemaining) {
                    $excess = $this->form_amount - $currentRemaining;
                    $student->increment('advance_balance', $excess);
                }
            }
        }

        $this->isPaymentModalOpen = false;
        $this->loadData();
        session()->flash('success', 'Transaction enregistrée avec succès.');
    }

    public function deletePayment($id)
    {
        Payment::findOrFail($id)->delete();
        $this->loadData();
    }

    public function approvePayment($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($this->isSuperAdmin()) {
            $payment->update(['status' => 'approved']);
            session()->flash('success', "Le décaissement {$payment->reference} a été approuvé avec succès.");
        } else {
            session()->flash('error', "Vous n'avez pas l'autorisation d'approuver cette transaction.");
        }
        
        $this->loadData();
    }

    public function downloadPdf($id)
    {
        $payment = Payment::with(['teacher'])->findOrFail($id);
        
        $pdf = \Barryvdh\Dompdf\Facade\Pdf::loadView('pdf.decaissement', [
            'payment' => $payment
        ]);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "decaissement-{$payment->reference}.pdf"
        );
    }

    public function isSuperAdmin()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) return false;
        
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }
        
        return isset($user->type) && $user->type === 'super_admin';
    }

    // --- Student Situation Actions ---

    public function openCardPaymentModal($studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->selectedStudent = $student->toArray();
        
        $feeTypesSum = FeeType::sum('amount');
        $paid = Payment::where('student_id', $student->id)->where('transaction_type', 'Entrée')->sum('amount');
        $remaining = max(0, $feeTypesSum - $paid - $student->advance_balance);

        $this->card_amount = $remaining;
        
        // Pre-select first fee type
        $firstFeeType = FeeType::first();
        $this->card_fee_type_id = $firstFeeType ? $firstFeeType->id : null;

        // Card simulator pre-fill specifically for student Walter
        if (strtolower($student->first_name) === 'walter') {
            $this->card_holder_name = $student->parent_name ?: 'Jean Walter';
            $this->card_number = '4242 4242 4242 4242';
            $this->card_expiry = '12/28';
            $this->card_cvv = '123';
        } else {
            $this->card_holder_name = $student->parent_name ?: '';
            $this->card_number = '';
            $this->card_expiry = '';
            $this->card_cvv = '';
        }

        $this->isCardPaymentModalOpen = true;
    }

    public function saveCardPayment()
    {
        $this->validate([
            'card_amount' => 'required|numeric|min:0',
            'card_fee_type_id' => 'required',
            'card_holder_name' => 'required|string',
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv' => 'required|string',
        ]);

        $student = Student::findOrFail($this->selectedStudent['id']);

        $payment = Payment::create([
            'transaction_type' => 'Entrée',
            'student_id' => $student->id,
            'fee_type_id' => $this->card_fee_type_id,
            'amount' => $this->card_amount,
            'payment_method' => 'Card',
            'currency' => 'USD',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'reference' => 'CARD-' . strtoupper(uniqid()),
            'description' => "Paiement par carte bancaire. Titulaire: {$this->card_holder_name}. Expiration: {$this->card_expiry}.",
            'ohada_account_code' => '706',
        ]);

        // Auto calculate advance balance if amount exceeds remaining balance due
        $feeTypesSum = FeeType::sum('amount');
        $due = $student->is_teacher_child ? 0.00 : $feeTypesSum;
        $paid = Payment::where('student_id', $student->id)->where('transaction_type', 'Entrée')
            ->where('id', '!=', $payment->id)->sum('amount');
        $currentRemaining = max(0, $due - $paid);
        
        if ($this->card_amount > $currentRemaining) {
            $excess = $this->card_amount - $currentRemaining;
            $student->increment('advance_balance', $excess);
        }

        $this->isCardPaymentModalOpen = false;
        $this->loadData();
        session()->flash('success', "Paiement par carte de {$this->card_amount}$ enregistré avec succès pour {$student->first_name}.");
    }

    public function openDerogationModal($studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->selectedStudent = $student->toArray();
        $this->selectedStudentIdForModal = $student->id;
        $this->derogation_is_teacher_child = $student->is_teacher_child;
        $this->derogation_has_derogation = $student->has_derogation;
        $this->derogation_notes = $student->derogation_notes;
        $this->derogation_advance_balance = $student->advance_balance;
        $this->isDerogationModalOpen = true;
    }

    public function saveDerogation()
    {
        $student = Student::findOrFail($this->selectedStudentIdForModal);
        $student->update([
            'is_teacher_child' => $this->derogation_is_teacher_child,
            'has_derogation' => $this->derogation_has_derogation,
            'derogation_notes' => $this->derogation_notes,
            'advance_balance' => $this->derogation_advance_balance,
        ]);

        $this->isDerogationModalOpen = false;
        $this->loadData();
        session()->flash('success', "Statut et dérogation de {$student->first_name} mis à jour avec succès.");
    }

    public function sendWhatsAppReminder($studentId)
    {
        $student = Student::findOrFail($studentId);
        $feeTypesSum = FeeType::sum('amount');
        $paid = Payment::where('student_id', $student->id)->where('transaction_type', 'Entrée')->sum('amount');
        $remaining = max(0, $feeTypesSum - $paid - $student->advance_balance);

        $msg = "Rappel de paiement : Bonjour {$student->parent_name}, nous vous informons que le solde des frais scolaires pour votre enfant {$student->first_name} {$student->last_name} présente un montant impayé de {$remaining} USD. Merci d'effectuer votre versement dans les plus brefs délais.";

        $result = \App\Services\WhatsAppService::sendMessage($student->parent_phone ?? '+243810000000', $msg);

        if ($result['success']) {
            session()->flash('success', "Rappel automatique WhatsApp envoyé au parent de {$student->first_name} !");
        } else {
            session()->flash('error', "Échec de l'envoi du rappel : " . $result['log']);
        }
    }

    public function sendLatePaymentAlert($studentId)
    {
        $student = Student::findOrFail($studentId);
        $feeTypesSum = FeeType::sum('amount');
        $paid = Payment::where('student_id', $student->id)->where('transaction_type', 'Entrée')->sum('amount');
        $remaining = max(0, $feeTypesSum - $paid - $student->advance_balance);

        $msg = "URGENT - Alerte de retard : Bonjour {$student->parent_name}, les frais scolaires (Trimestre / Inscription) de votre enfant {$student->first_name} {$student->last_name} accusent un retard de paiement. Solde débiteur : {$remaining} USD. Merci de régulariser immédiatement pour éviter le blocage de l'élève.";

        $result = \App\Services\WhatsAppService::sendMessage($student->parent_phone ?? '+243810000000', $msg);

        if ($result['success']) {
            session()->flash('success', "Alerte de retard de paiement (Frais Premier) envoyée au parent de {$student->first_name} !");
        } else {
            session()->flash('error', "Échec de l'envoi de l'alerte : " . $result['log']);
        }
    }

    public function openOhadaFilterModal()
    {
        $this->isOhadaFilterModalOpen = true;
    }

    public function closeOhadaFilterModal()
    {
        $this->isOhadaFilterModalOpen = false;
    }

    public function applyOhadaFilters()
    {
        $this->loadData();
        $this->isOhadaFilterModalOpen = false;
    }

    public function exportOhadaPdf()
    {
        // 1. Fetch OHADA approved transactions
        $queryRev = Payment::with(['student', 'feeType'])
            ->where('transaction_type', 'Entrée')
            ->where('status', 'approved');

        $queryChg = Payment::with(['teacher'])
            ->where('transaction_type', 'Sortie')
            ->where('status', 'approved');

        if ($this->ohada_start_date) {
            $queryRev->whereDate('payment_date', '>=', $this->ohada_start_date);
            $queryChg->whereDate('payment_date', '>=', $this->ohada_start_date);
        }
        if ($this->ohada_end_date) {
            $queryRev->whereDate('payment_date', '<=', $this->ohada_end_date);
            $queryChg->whereDate('payment_date', '<=', $this->ohada_end_date);
        }

        $revenus = $queryRev->orderBy('payment_date')->get()->toArray();
        $charges = $queryChg->orderBy('payment_date')->get()->toArray();

        $totalRev = collect($revenus)->sum('amount');
        $totalChg = collect($charges)->sum('amount');
        $resultat = $totalRev - $totalChg;

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
        $uniqueCode = strtoupper(substr(md5(time() . rand()), 0, 10));

        // Format dates for display
        $formattedStartDate = $this->ohada_start_date ? \Carbon\Carbon::parse($this->ohada_start_date)->format('d/m/Y') : 'Début';
        $formattedEndDate = $this->ohada_end_date ? \Carbon\Carbon::parse($this->ohada_end_date)->format('d/m/Y') : 'Fin';
        $periodLabel = "Période du {$formattedStartDate} au {$formattedEndDate}";

        // Digital signature QR data
        $qrData = "Yeschool Rapport OHADA\nCode: $uniqueCode\nEtablissement: $schoolName\nEdition: " . date('d/m/Y H:i') . "\nResultat: $" . number_format($resultat, 2) . "\nCertifie Conforme.";

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
            'revenus' => $revenus,
            'charges' => $charges,
            'totalRevenus' => $totalRev,
            'totalCharges' => $totalChg,
            'resultat' => $resultat,
            'periodLabel' => $periodLabel,
            'title' => "Rapport Comptable - Système OHADA",
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ohada-report', $data)->setPaper('a4', 'portrait');
        
        // Configure options directly on Dompdf
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        $this->isOhadaFilterModalOpen = false;

        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'rapport_ohada_' . $uniqueCode . '.pdf');
    }

    public function openRapportFilterModal()
    {
        $this->isRapportFilterModalOpen = true;
    }

    public function applyRapportFilter()
    {
        $this->loadData();
        $this->isRapportFilterModalOpen = false;
    }

    public function resetRapportFilter()
    {
        $this->reset(['rapport_class_id', 'rapport_fee_type_id', 'rapport_payment_method']);
        $this->rapport_start_date = date('Y-m-01');
        $this->rapport_end_date = date('Y-m-d');
        $this->loadData();
        $this->isRapportFilterModalOpen = false;
    }

    public function exportRapportCsv()
    {
        $filename = 'rapport_caisse_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            // Write BOM for Excel
            echo "\xEF\xBB\xBF";
            
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Date',
                'Matricule',
                'Élève',
                'Classe',
                'Type de Frais',
                'Méthode',
                'Référence',
                'Description',
                'Montant'
            ], ';');

            foreach ($this->rapportPayments as $p) {
                fputcsv($file, [
                    isset($p['payment_date']) ? \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') : '',
                    $p['student']['matricule'] ?? 'N/A',
                    ($p['student']['first_name'] ?? '') . ' ' . ($p['student']['last_name'] ?? ''),
                    $p['student']['school_class']['name'] ?? 'N/A',
                    $p['fee_type']['name'] ?? 'N/A',
                    $p['payment_method'] ?? 'Cash',
                    $p['reference'] ?? '',
                    $p['description'] ?? '',
                    number_format($p['amount'], 2, '.', '') . ' USD'
                ], ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportRapportPdf()
    {
        // 1. Fetch filtered transactions
        $query = Payment::with(['student.schoolClass', 'feeType'])
            ->where('transaction_type', 'Entrée')
            ->orderByDesc('payment_date');

        if ($this->rapport_start_date) {
            $query->whereDate('payment_date', '>=', $this->rapport_start_date);
        }
        if ($this->rapport_end_date) {
            $query->whereDate('payment_date', '<=', $this->rapport_end_date);
        }
        if ($this->rapport_class_id) {
            $query->whereHas('student', function($q) {
                $q->where('school_class_id', $this->rapport_class_id);
            });
        }
        if ($this->rapport_fee_type_id) {
            $query->where('fee_type_id', $this->rapport_fee_type_id);
        }
        if ($this->rapport_payment_method) {
            $query->where('payment_method', $this->rapport_payment_method);
        }

        $payments = $query->get();
        $totalUSD = $payments->sum('amount');

        // Fetch Inflow summaries (Jour, Mois, Année) - consistent with current date
        $totalJour = Payment::where('transaction_type', 'Entrée')
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount');

        $totalMois = Payment::where('transaction_type', 'Entrée')
            ->whereMonth('payment_date', Carbon::today()->month)
            ->whereYear('payment_date', Carbon::today()->year)
            ->sum('amount');

        $totalAnnee = Payment::where('transaction_type', 'Entrée')
            ->whereYear('payment_date', Carbon::today()->year)
            ->sum('amount');

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
        $uniqueCode = strtoupper(substr(md5(time() . rand()), 0, 10));

        // Format dates for display
        $formattedStartDate = $this->rapport_start_date ? \Carbon\Carbon::parse($this->rapport_start_date)->format('d/m/Y') : 'Début';
        $formattedEndDate = $this->rapport_end_date ? \Carbon\Carbon::parse($this->rapport_end_date)->format('d/m/Y') : 'Fin';
        
        $selectedClass = $this->rapport_class_id ? \App\Models\SchoolClass::find($this->rapport_class_id)?->name : 'Toutes';
        $selectedFee = $this->rapport_fee_type_id ? \App\Models\FeeType::find($this->rapport_fee_type_id)?->name : 'Tous';
        $selectedMethod = $this->rapport_payment_method ?: 'Toutes';

        $periodLabel = "Période du {$formattedStartDate} au {$formattedEndDate}";

        // Digital signature QR data
        $qrData = "Yeschool Rapport de Caisse\nCode: $uniqueCode\nEtablissement: $schoolName\nEdition: " . date('d/m/Y H:i') . "\nTotal Caisse: $" . number_format($totalUSD, 2) . "\nCertifie Conforme.";

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
            'payments' => $payments->toArray(),
            'totalUSD' => $totalUSD,
            'totalJour' => $totalJour,
            'totalMois' => $totalMois,
            'totalAnnee' => $totalAnnee,
            'periodLabel' => $periodLabel,
            'selectedClass' => $selectedClass,
            'selectedFee' => $selectedFee,
            'selectedMethod' => $selectedMethod,
            'title' => "Rapport de Caisse - Encaissements",
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapport-caisse', $data)->setPaper('a4', 'portrait');
        
        // Configure options directly on Dompdf
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);

        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'rapport_caisse_' . $uniqueCode . '.pdf');
    }
}
