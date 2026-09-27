<?php

namespace App\Services\Accounting;

use App\Enums\TreasuryAccountType;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Payslip;
use App\Models\Sale;
use App\Models\SupplierInvoice;
use App\Models\TreasuryAccount;
use App\Models\TreasuryTransfer;

/**
 * Alimente automatiquement la comptabilité générale à partir des opérations métier.
 */
class AccountingPoster
{
    public function __construct(private LedgerService $ledger, private AccountMap $accounts) {}

    /**
     * Facture client (commande d'imprimerie) : 411 au débit, 706 + TVA au crédit.
     */
    public function postCustomerInvoice(Invoice $invoice): JournalEntry
    {
        $net = round((float) $invoice->total - (float) $invoice->tax_total, 2);

        return $this->ledger->post('VEN', "Facture {$invoice->number}", [
            ['account' => $this->accounts->get('customers'), 'debit' => (float) $invoice->total, 'client_id' => $invoice->client_id],
            ['account' => $this->accounts->get('sales_services'), 'credit' => $net],
            ['account' => $this->accounts->get('vat_collected'), 'credit' => (float) $invoice->tax_total],
        ], $invoice, $invoice->site_id, $invoice->issued_at, $invoice->order?->number ?? $invoice->number);
    }

    /**
     * Encaissement client : trésorerie au débit, 411 au crédit.
     */
    public function postCustomerPayment(Payment $payment): JournalEntry
    {
        $treasury = $payment->treasuryAccount;

        return $this->ledger->post($this->journalFor($treasury), "Encaissement {$payment->number}", [
            ['account' => $this->treasuryLedgerCode($treasury), 'debit' => (float) $payment->amount],
            ['account' => $this->accounts->get('customers'), 'credit' => (float) $payment->amount, 'client_id' => $payment->client_id],
        ], $payment, $payment->site_id, $payment->paid_at, $payment->payable?->number ?? $payment->number);
    }

    /**
     * Vente comptoir : chaque moyen de paiement débite sa trésorerie ; 701 + TVA au crédit.
     */
    public function postSale(Sale $sale): JournalEntry
    {
        $lines = $sale->payments->map(fn (Payment $payment): array => [
            'account' => $this->treasuryLedgerCode($payment->treasuryAccount),
            'debit' => (float) $payment->amount,
            'label' => "Vente {$sale->number} — {$payment->method->label()}",
        ])->all();

        $cashed = round($sale->payments->sum(fn (Payment $payment): float => (float) $payment->amount), 2);
        $credit = round((float) $sale->total - $cashed, 2);

        if ($credit > 0) {
            $lines[] = ['account' => $this->accounts->get('customers'), 'debit' => $credit, 'client_id' => $sale->client_id, 'label' => "Vente {$sale->number} à crédit"];
        }

        $lines[] = ['account' => $this->accounts->get('sales_goods'), 'credit' => round((float) $sale->total - (float) $sale->tax_total, 2)];
        $lines[] = ['account' => $this->accounts->get('vat_collected'), 'credit' => (float) $sale->tax_total];

        return $this->ledger->post('VEN', "Vente comptoir {$sale->number}", $lines, $sale, $sale->site_id, $sale->sold_at, $sale->number);
    }

    public function postSupplierInvoice(SupplierInvoice $invoice): JournalEntry
    {
        return $this->ledger->post('ACH', "Facture fournisseur {$invoice->number}", [
            ['account' => $this->accounts->get('purchases_materials'), 'debit' => round((float) $invoice->total - (float) $invoice->tax_total, 2)],
            ['account' => $this->accounts->get('vat_deductible'), 'debit' => (float) $invoice->tax_total],
            ['account' => $this->accounts->get('suppliers'), 'credit' => (float) $invoice->total, 'supplier_id' => $invoice->supplier_id],
        ], $invoice, $invoice->site_id, $invoice->invoice_date, $invoice->supplier_reference);
    }

    public function postSupplierPayment(Payment $payment): JournalEntry
    {
        $treasury = $payment->treasuryAccount;

        return $this->ledger->post($this->journalFor($treasury), "Règlement fournisseur {$payment->number}", [
            ['account' => $this->accounts->get('suppliers'), 'debit' => (float) $payment->amount, 'supplier_id' => $payment->supplier_id],
            ['account' => $this->treasuryLedgerCode($treasury), 'credit' => (float) $payment->amount],
        ], $payment, $payment->site_id, $payment->paid_at);
    }

    public function postExpenseClaimPayment(ExpenseClaim $claim, Payment $payment): JournalEntry
    {
        return $this->ledger->post($this->journalFor($payment->treasuryAccount), "Note de frais {$claim->number}", [
            ['account' => $this->accounts->get('staff_expenses'), 'debit' => (float) $claim->amount],
            ['account' => $this->treasuryLedgerCode($payment->treasuryAccount), 'credit' => (float) $claim->amount],
        ], $claim, $claim->site_id, $payment->paid_at);
    }

    /**
     * Décaissement de caisse (bon de décaissement) imputé sur un compte de charge.
     */
    public function postCashDisbursement(TreasuryAccount $treasury, float $amount, string $description, string $expenseAccount, ?int $siteId = null): JournalEntry
    {
        return $this->ledger->post($this->journalFor($treasury), $description, [
            ['account' => $expenseAccount, 'debit' => $amount],
            ['account' => $this->treasuryLedgerCode($treasury), 'credit' => $amount],
        ], null, $siteId);
    }

    public function postCashDifference(TreasuryAccount $treasury, float $difference, string $description, ?int $siteId = null): JournalEntry
    {
        $lines = $difference < 0
            ? [['account' => $this->accounts->get('cash_differences'), 'debit' => abs($difference)], ['account' => $this->treasuryLedgerCode($treasury), 'credit' => abs($difference)]]
            : [['account' => $this->treasuryLedgerCode($treasury), 'debit' => $difference], ['account' => '758', 'credit' => $difference]];

        return $this->ledger->post($this->journalFor($treasury), $description, $lines, null, $siteId);
    }

    public function postTreasuryTransfer(TreasuryTransfer $transfer): JournalEntry
    {
        return $this->ledger->post('OD', "Virement interne {$transfer->number}", [
            ['account' => $this->treasuryLedgerCode($transfer->to), 'debit' => (float) $transfer->amount],
            ['account' => $this->treasuryLedgerCode($transfer->from), 'credit' => (float) $transfer->amount],
        ], $transfer, $transfer->from->site_id, $transfer->transferred_at);
    }

    public function postPayslip(Payslip $payslip): JournalEntry
    {
        return $this->ledger->post('OD', "Paie {$payslip->period->code} — {$payslip->employee->fullName()}", [
            ['account' => $this->accounts->get('salaries'), 'debit' => (float) $payslip->gross_earnings],
            ['account' => $this->accounts->get('salaries_payable'), 'credit' => (float) $payslip->net_pay],
            ['account' => $this->accounts->get('staff_advances'), 'credit' => (float) $payslip->total_deductions],
        ], $payslip, $payslip->employee->site_id, $payslip->period->ends_on);
    }

    public function treasuryLedgerCode(?TreasuryAccount $treasury): string
    {
        if ($treasury?->account) {
            return $treasury->account->code;
        }

        return match ($treasury?->type) {
            TreasuryAccountType::Bank => $this->accounts->get('bank'),
            TreasuryAccountType::MobileMoney => $this->accounts->get('mobile_money'),
            default => $this->accounts->get('cash'),
        };
    }

    private function journalFor(?TreasuryAccount $treasury): string
    {
        if ($treasury?->journal) {
            return $treasury->journal->code;
        }

        return match ($treasury?->type) {
            TreasuryAccountType::Bank => 'BQ',
            TreasuryAccountType::MobileMoney => 'MM',
            default => 'CAI',
        };
    }
}
