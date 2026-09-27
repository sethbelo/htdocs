<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PayrollItemType;
use App\Enums\PurchaseRequestStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\PurchaseRequest;
use App\Services\Approvals\ApprovalService;
use App\Services\Hr\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class ApprovalAndPayrollTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    private function purchaseRequest(float $amount): PurchaseRequest
    {
        return PurchaseRequest::query()->create([
            'number' => 'DA-TEST-'.uniqid(), 'site_id' => $this->headquarters->id, 'requested_by' => $this->userWithRole('magasinier')->id,
            'status' => PurchaseRequestStatus::PendingApproval, 'estimated_total' => $amount, 'justification' => 'Papier',
        ]);
    }

    public function test_amount_thresholds_add_approval_steps(): void
    {
        $approvals = app(ApprovalService::class);
        $admin = $this->userWithRole('responsable-administratif');
        $finance = $this->userWithRole('responsable-financier');
        $director = $this->userWithRole('directeur-general');

        $small = $this->purchaseRequest(100);
        $request = $approvals->submit($small, $small->requester);
        $approvals->approve($request, $admin);
        $this->assertSame(ApprovalStatus::Approved, $request->fresh()->status);
        $this->assertSame(PurchaseRequestStatus::Approved, $small->fresh()->status);

        $large = $this->purchaseRequest(5000);
        $request = $approvals->submit($large, $large->requester);
        $approvals->approve($request, $admin);
        $this->assertSame('Responsable financier', $request->fresh()->currentStep->name);

        try {
            $approvals->approve($request->fresh(), $admin);
            $this->fail('Étape non attribuée.');
        } catch (BusinessRuleException) {
        }

        $approvals->approve($request->fresh(), $finance);
        $approvals->approve($request->fresh(), $director);
        $this->assertSame(ApprovalStatus::Approved, $request->fresh()->status);
        $this->assertCount(3, $request->actions);
    }

    public function test_rejection_requires_reason_and_comment(): void
    {
        $approvals = app(ApprovalService::class);
        $pr = $this->purchaseRequest(100);
        $request = $approvals->submit($pr, $pr->requester);

        try {
            $approvals->reject($request, $this->userWithRole('responsable-administratif'), '', '');
            $this->fail('Refus sans motif accepté.');
        } catch (BusinessRuleException) {
        }

        $approvals->reject($request->fresh(), $this->userWithRole('responsable-administratif'), 'Budget épuisé', 'Reporter au mois prochain');
        $this->assertSame(PurchaseRequestStatus::Rejected, $pr->fresh()->status);
        $this->assertSame('Budget épuisé', $request->actions()->first()->reason);
    }

    public function test_payroll_net_pay_formula(): void
    {
        $employee = Employee::factory()->create(['base_salary' => '520', 'site_id' => $this->headquarters->id]);
        $period = PayrollPeriod::query()->create(['code' => now()->format('Y-m'), 'starts_on' => now()->startOfMonth(), 'ends_on' => now()->endOfMonth()]);

        $period->items()->create(['employee_id' => $employee->id, 'type' => PayrollItemType::Bonus, 'label' => 'Prime de rendement', 'amount' => 50]);
        $period->items()->create(['employee_id' => $employee->id, 'type' => PayrollItemType::Deduction, 'label' => 'Retenue', 'amount' => 10]);
        EmployeeLoan::query()->create(['employee_id' => $employee->id, 'type' => 'advance', 'principal' => 100, 'installment' => 40, 'balance' => 100, 'granted_on' => today()]);
        Attendance::query()->create(['employee_id' => $employee->id, 'date' => now()->startOfMonth(), 'status' => AttendanceStatus::Absent]);

        $payroll = app(PayrollService::class);
        $payroll->compute($period);
        $payslip = $period->payslips()->where('employee_id', $employee->id)->firstOrFail();

        $this->assertEquals(570, (float) $payslip->gross_earnings);
        $this->assertEquals(round(20 + 10 + 40, 2), (float) $payslip->total_deductions);
        $this->assertEquals(500, (float) $payslip->net_pay);

        $payroll->validate($period->fresh(), $this->userWithRole('responsable-rh'));
        $this->assertEquals(60, (float) EmployeeLoan::query()->value('balance'));
    }
}
