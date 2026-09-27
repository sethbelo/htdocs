<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\Payslip;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProHRPayroll extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-payroll';
    protected static ?string $title = 'Rémunération & Bulletins Paie';

    // Disable default navigation registration, we link it manually in our sidebar
    protected static bool $shouldRegisterNavigation = false;

    public $payslipsList = [];
    public $personnelList = [];

    // Payslip Form Fields
    public $isPayslipOffcanvasOpen = false;
    public $payslip_id;
    public $form_ps_teacher_id;
    public $form_ps_month_year;
    public $form_ps_base_salary;
    public $form_ps_bonus = 0.00;
    public $form_ps_deductions = 0.00;
    public $form_ps_status = 'Brouillon';
    public $form_ps_payment_date;
    public $form_ps_payment_method = 'Virement';
    public $form_ps_notes;

    // Payslip preview overlay
    public $viewing_payslip_id = null;

    // Detailed Payroll Report State
    public $filter_teacher_id;
    public $payroll_start_date;
    public $payroll_end_date;
    public $isDetailedPayrollModalOpen = false;

    // Global Payroll Report State
    public $isGlobalReportModalOpen = false;
    public $report_start_date;
    public $report_end_date;

    // Analysis Modal State
    public $isAnalysisModalOpen = false;
    public $chartHistory = [];
    public $chartSpecialties = [];

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;
    public $confirmDeleteAction;
    public $confirmDeleteMessage;

    // Simulation Tool State
    public $simulated_teacher_id;
    public $simulated_base_salary = 0.00;
    public $simulated_bonus = 0.00;
    public $simulated_deductions = 0.00;
    public $simulated_net = 0.00;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        $this->loadData();
    }

    public function loadData()
    {
        $this->payslipsList = Payslip::with('teacher')->orderByDesc('created_at')->get()->toArray();
        $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
        
        // Load Chart Data
        $allPayslips = Payslip::with('teacher')->get();
        
        $this->chartHistory = $allPayslips->groupBy('month_year')
            ->map(function ($group, $monthYear) {
                return [
                    'month_year' => $monthYear,
                    'total_net' => floatval($group->sum('net_salary')),
                    'total_bonus' => floatval($group->sum('bonus')),
                    'total_deductions' => floatval($group->sum('deductions')),
                ];
            })
            ->sortBy(function ($item) {
                try {
                    return Carbon::createFromFormat('m-Y', $item['month_year'])->timestamp;
                } catch (\Exception $e) {
                    return 0;
                }
            })
            ->values()
            ->toArray();

        $this->chartSpecialties = $allPayslips->groupBy('teacher.specialty')
            ->map(function ($group, $specialty) {
                return [
                    'specialty' => $specialty ?: 'Autre',
                    'total_net' => floatval($group->sum('net_salary')),
                ];
            })
            ->values()
            ->toArray();

        if (!$this->simulated_teacher_id && !empty($this->personnelList)) {
            $this->simulated_teacher_id = $this->personnelList[0]['id'];
            $teacher = Teacher::find($this->simulated_teacher_id);
            $this->simulated_base_salary = $teacher ? floatval($teacher->salary) : 0.00;
            
            // Pre-load first teacher's latest payslip bonus and deductions
            $latestPayslip = Payslip::where('teacher_id', $this->simulated_teacher_id)
                ->orderByDesc('created_at')
                ->first();
            if ($latestPayslip) {
                $this->simulated_bonus = floatval($latestPayslip->bonus);
                $this->simulated_deductions = floatval($latestPayslip->deductions);
            } else {
                $this->simulated_bonus = 0.00;
                $this->simulated_deductions = 0.00;
            }
            $this->simulateNetSalary();
        }
    }

    public function simulateNetSalary()
    {
        if (!$this->simulated_teacher_id) return;
        $base = floatval($this->simulated_base_salary) ?: 0;
        $bonus = floatval($this->simulated_bonus) ?: 0;
        $deductions = floatval($this->simulated_deductions) ?: 0;
        $this->simulated_net = $base + $bonus - $deductions;
    }

    public function updatedSimulatedTeacherId($value)
    {
        if ($value) {
            $teacher = Teacher::find($value);
            if ($teacher) {
                $this->simulated_base_salary = floatval($teacher->salary);
                $latestPayslip = Payslip::where('teacher_id', $value)
                    ->orderByDesc('created_at')
                    ->first();
                if ($latestPayslip) {
                    $this->simulated_bonus = floatval($latestPayslip->bonus);
                    $this->simulated_deductions = floatval($latestPayslip->deductions);
                } else {
                    $this->simulated_bonus = 0.00;
                    $this->simulated_deductions = 0.00;
                }
            }
        } else {
            $this->simulated_base_salary = 0.00;
            $this->simulated_bonus = 0.00;
            $this->simulated_deductions = 0.00;
        }
        $this->simulateNetSalary();
    }

    public function updatedSimulatedBaseSalary() { $this->simulateNetSalary(); }
    public function updatedSimulatedBonus() { $this->simulateNetSalary(); }
    public function updatedSimulatedDeductions() { $this->simulateNetSalary(); }

    public function updatedFormPsTeacherId() { $this->autoCalculatePayslipDeductions(); }
    public function updatedFormPsMonthYear() { $this->autoCalculatePayslipDeductions(); }

    public function autoCalculatePayslipDeductions()
    {
        if (!$this->form_ps_teacher_id || !$this->form_ps_month_year) {
            return;
        }

        $teacher = Teacher::find($this->form_ps_teacher_id);
        if (!$teacher) return;

        // Auto populate base salary
        $this->form_ps_base_salary = floatval($teacher->salary);

        // Parse month_year (e.g. '07-2026')
        try {
            $monthStart = Carbon::createFromFormat('m-Y', $this->form_ps_month_year)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('m-Y', $this->form_ps_month_year)->endOfMonth();
        } catch (\Exception $e) {
            return;
        }

        // Find approved unpaid leaves during this month
        $unpaidLeaves = LeaveRequest::where('teacher_id', $this->form_ps_teacher_id)
            ->where('type', 'Congé sans solde')
            ->where('status', 'Approuvé')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                  ->orWhereBetween('end_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                  ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                      $sub->where('start_date', '<=', $monthStart->format('Y-m-d'))
                          ->where('end_date', '>=', $monthEnd->format('Y-m-d'));
                  });
            })
            ->get();

        $unpaidDays = 0;
        foreach ($unpaidLeaves as $leave) {
            $leaveStart = $leave->start_date;
            $leaveEnd = $leave->end_date;

            // Clamp to current month
            $start = $leaveStart->lt($monthStart) ? $monthStart->copy() : $leaveStart->copy();
            $end = $leaveEnd->gt($monthEnd) ? $monthEnd->copy() : $leaveEnd->copy();

            $unpaidDays += $start->diffInDays($end) + 1;
        }

        if ($unpaidDays > 0) {
            $deduction = round(($teacher->salary / 30) * $unpaidDays, 2);
            $this->form_ps_deductions = $deduction;
            $this->form_ps_notes = "Déduction de {$unpaidDays} jours de congé sans solde pour un total de {$deduction} $.";
        } else {
            $this->form_ps_deductions = 0.00;
            $this->form_ps_notes = "";
        }
    }

    public function createPayslip()
    {
        $this->resetValidation();
        $this->reset(['payslip_id', 'form_ps_teacher_id', 'form_ps_month_year', 'form_ps_base_salary', 'form_ps_bonus', 'form_ps_deductions', 'form_ps_status', 'form_ps_payment_date', 'form_ps_payment_method', 'form_ps_notes']);
        $this->form_ps_month_year = date('m-Y');
        $this->form_ps_status = 'Brouillon';
        $this->isPayslipOffcanvasOpen = true;
    }

    public function editPayslip($id)
    {
        $this->resetValidation();
        $p = Payslip::findOrFail($id);
        $this->payslip_id = $p->id;
        $this->form_ps_teacher_id = $p->teacher_id;
        $this->form_ps_month_year = $p->month_year;
        $this->form_ps_base_salary = $p->base_salary;
        $this->form_ps_bonus = $p->bonus;
        $this->form_ps_deductions = $p->deductions;
        $this->form_ps_status = $p->status;
        $this->form_ps_payment_date = $p->payment_date?->format('Y-m-d');
        $this->form_ps_payment_method = $p->payment_method;
        $this->form_ps_notes = $p->notes;
        $this->isPayslipOffcanvasOpen = true;
    }

    public function savePayslip()
    {
        $this->validate([
            'form_ps_teacher_id' => 'required|exists:teachers,id',
            'form_ps_month_year' => 'required|string',
            'form_ps_base_salary' => 'required|numeric',
        ]);

        $base = floatval($this->form_ps_base_salary);
        $bonus = floatval($this->form_ps_bonus) ?: 0;
        $deductions = floatval($this->form_ps_deductions) ?: 0;
        $net = $base + $bonus - $deductions;

        Payslip::updateOrCreate(
            ['id' => $this->payslip_id],
            [
                'teacher_id' => $this->form_ps_teacher_id,
                'month_year' => $this->form_ps_month_year,
                'base_salary' => $base,
                'bonus' => $bonus,
                'deductions' => $deductions,
                'net_salary' => $net,
                'status' => $this->form_ps_status,
                'payment_date' => $this->form_ps_payment_date,
                'payment_method' => $this->form_ps_payment_method,
                'notes' => $this->form_ps_notes,
            ]
        );

        $this->isPayslipOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Bulletin de paie enregistré.');
    }

    public function markAsPaid($id)
    {
        $p = Payslip::findOrFail($id);
        $p->update([
            'status' => 'Payé',
            'payment_date' => now(),
            'payment_method' => 'Virement',
        ]);
        $this->loadData();
        session()->flash('success', 'Bulletin de paie marqué comme payé.');
    }

    public function deletePayslip($id)
    {
        Payslip::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('success', 'Bulletin de paie supprimé.');
    }

    public function viewPayslip($id)
    {
        $this->viewing_payslip_id = $id;
    }

    public function closePayslipView()
    {
        $this->viewing_payslip_id = null;
    }

    public function openDetailedPayrollModal()
    {
        $this->filter_teacher_id = '';
        $this->payroll_start_date = date('Y-01-01');
        $this->payroll_end_date = date('Y-m-d');
        $this->isDetailedPayrollModalOpen = true;
    }

    public function closeDetailedPayrollModal()
    {
        $this->isDetailedPayrollModalOpen = false;
    }

    public function exportSinglePayslip($id)
    {
        $payslip = Payslip::with('teacher')->findOrFail($id);
        
        $school = auth()->user()->school ?? \App\Models\School::first();
        $schoolName = $school ? $school->name : 'Yeschool Établissement';
        $schoolEmail = $school ? $school->email : 'contact@yeschool.com';
        $schoolPhone = $school ? $school->phone : '+243 000 000 000';
        $schoolAddress = $school ? $school->address : 'Adresse non spécifiée';
        
        $logoBase64 = null;
        if ($school && $school->logo) {
            $logoPath = public_path('storage/' . $school->logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . $logoData;
            }
        }

        $uniqueCode = strtoupper(substr(md5(time() . $payslip->id), 0, 10));
        $qrData = "Yeschool Bulletin de Paie\nCode: $uniqueCode\nEtablissement: $schoolName\nEmploye: {$payslip->teacher->first_name} {$payslip->teacher->last_name}\nMois: {$payslip->month_year}\nNet: $" . number_format($payslip->net_salary, 2) . "\nCertifie Conforme.";

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
        }

        $data = [
            'payslip' => $payslip,
            'title' => "Bulletin de Paie - " . $payslip->month_year,
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.single-payslip', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        return response()->streamDownload(function () use ($pdf, $uniqueCode) {
            echo $pdf->output();
        }, 'bulletin_paie_' . $uniqueCode . '.pdf');
    }

    public function exportDetailedPayrollSheet()
    {
        $this->validate([
            'filter_teacher_id' => 'required|exists:teachers,id',
            'payroll_start_date' => 'required|date',
            'payroll_end_date' => 'required|date|after_or_equal:payroll_start_date',
        ]);

        $teacher = Teacher::findOrFail($this->filter_teacher_id);
        
        $payslips = Payslip::where('teacher_id', $this->filter_teacher_id)
            ->where('status', 'Payé')
            ->whereBetween('payment_date', [$this->payroll_start_date, $this->payroll_end_date])
            ->orderBy('payment_date')
            ->get();

        $totalBase = $payslips->sum('base_salary');
        $totalBonus = $payslips->sum('bonus');
        $totalDeductions = $payslips->sum('deductions');
        $totalNet = $payslips->sum('net_salary');

        $school = auth()->user()->school ?? \App\Models\School::first();
        $schoolName = $school ? $school->name : 'Yeschool Établissement';
        $schoolEmail = $school ? $school->email : 'contact@yeschool.com';
        $schoolPhone = $school ? $school->phone : '+243 000 000 000';
        $schoolAddress = $school ? $school->address : 'Adresse non spécifiée';
        
        $logoBase64 = null;
        if ($school && $school->logo) {
            $logoPath = public_path('storage/' . $school->logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . $logoData;
            }
        }

        $uniqueCode = strtoupper(substr(md5(time() . rand()), 0, 10));
        $formattedStart = \Carbon\Carbon::parse($this->payroll_start_date)->format('d/m/Y');
        $formattedEnd = \Carbon\Carbon::parse($this->payroll_end_date)->format('d/m/Y');
        $periodLabel = "Période du {$formattedStart} au {$formattedEnd}";

        $qrData = "Yeschool Fiche De Paie\nCode: $uniqueCode\nEtablissement: $schoolName\nEmploye: {$teacher->first_name} {$teacher->last_name}\nPeriod: $periodLabel\nTotal Net: $" . number_format($totalNet, 2) . "\nCertifie Conforme.";

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
        }

        $data = [
            'teacher' => $teacher,
            'payslips' => $payslips,
            'totalBase' => $totalBase,
            'totalBonus' => $totalBonus,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'periodLabel' => $periodLabel,
            'title' => "Fiche Détaillée de Paie - " . $teacher->first_name . ' ' . $teacher->last_name,
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.detailed-payroll', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        $this->isDetailedPayrollModalOpen = false;

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'fiche_paie_' . $uniqueCode . '.pdf');
    }

    public function openGlobalReportModal()
    {
        $this->report_start_date = date('Y-01-01');
        $this->report_end_date = date('Y-m-d');
        $this->isGlobalReportModalOpen = true;
    }

    public function closeGlobalReportModal()
    {
        $this->isGlobalReportModalOpen = false;
    }

    public function openAnalysisModal()
    {
        $this->loadData(); // Refresh chart data
        $this->isAnalysisModalOpen = true;
    }

    public function closeAnalysisModal()
    {
        $this->isAnalysisModalOpen = false;
    }

    public function exportGlobalPayrollPdf()
    {
        $this->validate([
            'report_start_date' => 'required|date',
            'report_end_date' => 'required|date|after_or_equal:report_start_date',
        ]);

        $payslips = Payslip::with('teacher')
            ->where('status', 'Payé')
            ->whereBetween('payment_date', [$this->report_start_date, $this->report_end_date])
            ->orderBy('payment_date')
            ->get();

        // Calculate Masse Salariale Totals
        $totalBase = $payslips->sum('base_salary');
        $totalBonus = $payslips->sum('bonus');
        $totalDeductions = $payslips->sum('deductions');
        $totalNet = $payslips->sum('net_salary');

        // Fetch School Information
        $school = auth()->user()->school ?? \App\Models\School::first();
        $schoolName = $school ? $school->name : 'Yeschool Établissement';
        $schoolEmail = $school ? $school->email : 'contact@yeschool.com';
        $schoolPhone = $school ? $school->phone : '+243 000 000 000';
        $schoolAddress = $school ? $school->address : 'Adresse non spécifiée';
        
        $logoBase64 = null;
        if ($school && $school->logo) {
            $logoPath = public_path('storage/' . $school->logo);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . $logoData;
            }
        }

        $uniqueCode = strtoupper(substr(md5(time() . rand()), 0, 10));
        $formattedStart = \Carbon\Carbon::parse($this->report_start_date)->format('d/m/Y');
        $formattedEnd = \Carbon\Carbon::parse($this->report_end_date)->format('d/m/Y');
        $periodLabel = "Période du {$formattedStart} au {$formattedEnd}";

        $data = [
            'payslips' => $payslips,
            'totalBase' => $totalBase,
            'totalBonus' => $totalBonus,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'periodLabel' => $periodLabel,
            'title' => "Rapport de Masse Salariale",
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolPhone' => $schoolPhone,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'uniqueCode' => $uniqueCode,
            'footer' => "Généré par Yeschool - " . date('d/m/Y H:i')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.global-payroll-report', $data)->setPaper('a4', 'portrait');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isHtml5ParserEnabled', true);
        
        $this->isGlobalReportModalOpen = false;

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'rapport_masse_salariale_' . $uniqueCode . '.pdf');
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
}
