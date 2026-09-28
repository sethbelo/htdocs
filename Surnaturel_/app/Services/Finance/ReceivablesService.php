<?php

namespace App\Services\Finance;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\VoucherStatus;
use App\Enums\VoucherType;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Accounting\AccountingPoster;
use App\Services\Accounting\AccountMap;
use App\Services\Accounting\LedgerService;
use App\Services\Cash\TreasuryService;
use App\Services\Sales\InvoiceService;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finance : encaissement des créances clients hors caisse (virement, chèque, Mobile Money),
 * avoirs et annulations de factures, exécution (paiement) des bons approuvés.
 */
class ReceivablesService
{
    public function __construct(
        private NumberGenerator $numbers,
        private AccountingPoster $poster,
        private LedgerService $ledger,
        private AccountMap $accounts,
        private TreasuryService $treasury,
        private InvoiceService $invoices,
    ) {}

    /**
     * Encaissement d'une facture sans commande (vente à crédit, facture directe).
     * Les factures de commande s'encaissent en caisse commandes pour mettre à jour la commande.
     */
    public function collect(Invoice $invoice, TreasuryAccount $account, PaymentMethod $method, float $amount, User $user, ?string $reference = null): Payment
    {
        $this->ensureOpen($invoice);

        if ($invoice->order_id) {
            throw new BusinessRuleException('Cette facture est liée à une commande : encaissez-la en caisse commandes.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > round($invoice->balanceDue(), 2)) {
            throw new BusinessRuleException('Montant invalide : il doit être positif et ne pas dépasser le reste dû ('.number_format($invoice->balanceDue(), 2, ',', ' ').').');
        }

        return DB::transaction(function () use ($invoice, $account, $method, $amount, $user, $reference): Payment {
            $payment = Payment::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Payment),
                'batch' => (string) Str::uuid(),
                'direction' => 'in',
                'payable_type' => $invoice->getMorphClass(),
                'payable_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_id' => $invoice->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'treasury_account_id' => $account->id,
                'site_id' => $invoice->site_id,
                'received_by' => $user->id,
                'paid_at' => now(),
            ]);

            $this->treasury->credit($account, $amount);
            $this->poster->postCustomerPayment($payment->setRelation('treasuryAccount', $account)->setRelation('payable', $invoice));
            $this->invoices->applyPayment($invoice, $amount);

            return $payment;
        });
    }

    /**
     * Avoir : réduit la créance du client (écriture inverse de la facture, au prorata de la TVA).
     */
    public function creditNote(Invoice $invoice, float $amount, string $reason, User $user): Invoice
    {
        $this->ensureOpen($invoice);

        if ($invoice->type === InvoiceType::CreditNote) {
            throw new BusinessRuleException('Un avoir ne peut pas faire l’objet d’un avoir.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > round($invoice->balanceDue(), 2)) {
            throw new BusinessRuleException('Le montant de l’avoir doit être positif et ne pas dépasser le reste dû. Pour une facture déjà réglée, procédez par remboursement (bon de décaissement).');
        }

        if (blank($reason)) {
            throw new BusinessRuleException('Le motif de l’avoir est obligatoire.');
        }

        return DB::transaction(function () use ($invoice, $amount, $reason, $user): Invoice {
            $ratio = (float) $invoice->total > 0 ? $amount / (float) $invoice->total : 0;
            $tax = round((float) $invoice->tax_total * $ratio, 2);

            $credit = Invoice::query()->create([
                'number' => $this->numbers->next(NumberGenerator::CreditNote),
                'type' => InvoiceType::CreditNote,
                'order_id' => $invoice->order_id,
                'sale_id' => $invoice->sale_id,
                'parent_invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'site_id' => $invoice->site_id,
                'status' => InvoiceStatus::Paid,
                'issued_at' => now(),
                'subtotal' => round($amount - $tax, 2),
                'discount_total' => 0,
                'tax_total' => $tax,
                'total' => $amount,
                'amount_paid' => $amount,
                'created_by' => $user->id,
            ]);
            $credit->items()->create(['description' => "Avoir sur facture {$invoice->number} — {$reason}", 'quantity' => 1, 'unit_price' => round($amount - $tax, 2), 'tax_rate' => $amount - $tax > 0 ? round($tax / ($amount - $tax) * 100, 2) : 0, 'discount' => 0, 'total' => $amount]);

            $this->ledger->post('VEN', "Avoir {$credit->number} sur facture {$invoice->number}", [
                ['account' => $this->accounts->get($invoice->sale_id ? 'sales_goods' : 'sales_services'), 'debit' => round($amount - $tax, 2), 'label' => $reason],
                ['account' => $this->accounts->get('vat_collected'), 'debit' => $tax],
                ['account' => $this->accounts->get('customers'), 'credit' => $amount, 'client_id' => $invoice->client_id],
            ], $credit, $invoice->site_id, now(), $invoice->number);

            $this->invoices->applyPayment($invoice, $amount);

            return $credit;
        });
    }

    /**
     * Annulation d'une facture non réglée : avoir total automatique puis statut « Annulée ».
     */
    public function cancel(Invoice $invoice, string $reason, User $user): Invoice
    {
        $this->ensureOpen($invoice);

        if ((float) $invoice->amount_paid > 0) {
            throw new BusinessRuleException('Facture partiellement réglée : émettez un avoir pour le reste dû.');
        }

        return DB::transaction(function () use ($invoice, $reason, $user): Invoice {
            $credit = $this->creditNote($invoice, (float) $invoice->total, "Annulation : {$reason}", $user);
            $invoice->update(['status' => InvoiceStatus::Cancelled]);

            return $credit;
        });
    }

    /**
     * Exécution d'un bon approuvé : décaissement (bon de caisse / de décaissement) imputé en charge,
     * ou simple constat d'exécution pour les autres bons.
     */
    public function executeVoucher(Voucher $voucher, User $user, ?TreasuryAccount $account = null, ?string $expenseAccount = null): Voucher
    {
        if ($voucher->status !== VoucherStatus::Approved) {
            throw new BusinessRuleException('Seul un bon approuvé peut être exécuté.');
        }

        $pays = in_array($voucher->type, [VoucherType::DisbursementVoucher, VoucherType::CashVoucher], true);

        if ($pays && ! $account) {
            throw new BusinessRuleException('Choisissez le compte de trésorerie à débiter.');
        }

        if ($pays && (float) $account->balance < (float) $voucher->amount) {
            throw new BusinessRuleException("Solde insuffisant sur {$account->name}.");
        }

        return DB::transaction(function () use ($voucher, $user, $account, $expenseAccount, $pays): Voucher {
            if ($pays) {
                $this->treasury->debit($account, (float) $voucher->amount);
                $this->poster->postCashDisbursement($account, (float) $voucher->amount, "Bon {$voucher->number} — ".($voucher->description ?: $voucher->beneficiary), $expenseAccount ?: $this->accounts->get('other_purchases'), $voucher->site_id);
            }

            $voucher->update(['status' => VoucherStatus::Executed]);
            activity('Voucher')->performedOn($voucher)->causedBy($user)->log($pays ? "Bon payé depuis {$account->name}" : 'Bon exécuté');

            return $voucher;
        });
    }

    private function ensureOpen(Invoice $invoice): void
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new BusinessRuleException('Facture annulée.');
        }
    }
}
