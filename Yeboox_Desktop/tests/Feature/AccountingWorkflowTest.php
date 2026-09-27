<?php

namespace Tests\Feature;

use App\Models\AccountingRecord;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_payment_and_credit_note_create_balanced_ohada_entries(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['name' => 'Client OHADA', 'is_active' => true]);
        $invoice = Invoice::create([
            'number' => 'FAC-2026-0001', 'customer_id' => $customer->id,
            'invoice_date' => '2026-08-31', 'due_date' => '2026-09-30', 'status' => 'draft',
            'subtotal' => 1000, 'tax_percent' => 16, 'tax_amount' => 160,
            'discount_amount' => 0, 'total_amount' => 1160, 'paid_amount' => 0,
            'user_id' => auth()->id(),
        ]);
        $service = app(AccountingService::class);

        $service->postInvoice($invoice);
        $service->recordPayment($invoice, [
            'payment_date' => '2026-08-31', 'amount' => 500, 'method' => 'cash',
            'treasury_account' => '5711', 'reference' => 'RC-1', 'notes' => null,
        ]);
        $service->createCreditNote($invoice, [
            'credit_date' => '2026-08-31', 'subtotal' => 500, 'tax_amount' => 160,
            'reason' => 'Retour de marchandise',
        ]);

        $this->assertEqualsWithDelta(0, $invoice->fresh()->balance_due, 0.001);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertJournalIsBalanced('FAC-2026-0001');
        $this->assertJournalIsBalanced('REG-2026-0001');
        $this->assertJournalIsBalanced('AV-2026-0001');
        $this->assertDatabaseHas('accounting_records', ['journal_code' => 'VE', 'syscohada_code' => '4431', 'credit' => 160]);
    }

    public function test_expense_posts_charge_tax_and_treasury_lines(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = app(AccountingService::class)->createExpense([
            'expense_date' => '2026-08-31', 'supplier' => 'Fournisseur test',
            'description' => 'Fournitures', 'expense_account' => '605',
            'payment_method' => 'bank', 'credit_account' => '5211',
            'subtotal' => 100, 'tax_amount' => 16, 'receipt_reference' => 'FF-10', 'notes' => null,
        ]);

        $this->assertSame('DEP-2026-0001', $expense->number);
        $this->assertJournalIsBalanced($expense->number);
        $this->assertDatabaseHas('accounting_records', ['reference' => $expense->number, 'syscohada_code' => '4452', 'debit' => 16]);
        $this->assertDatabaseHas('accounting_records', ['reference' => $expense->number, 'syscohada_code' => '5211', 'credit' => 116]);
    }

    private function assertJournalIsBalanced(string $reference): void
    {
        $records = AccountingRecord::where('reference', $reference)->get();
        $this->assertNotEmpty($records);
        $this->assertEqualsWithDelta((float) $records->sum('debit'), (float) $records->sum('credit'), 0.001);
    }
}
