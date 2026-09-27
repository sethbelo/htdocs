<?php

namespace App\Services;

use App\Actions\Payments\RecordPayment;
use App\Enums\DocumentType;
use App\Models\BankTransaction;
use App\Models\CashSession;
use App\Models\Document;
use App\Models\FinancialAccount;
use App\Models\Payment;
use App\Models\SalesOrder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Caisse : sessions (ouverture / clôture avec écart), encaissement des
 * factures et mise à jour automatique du suivi de commande.
 */
class CashService
{
    public function __construct(private readonly SequenceService $sequences, private readonly RecordPayment $payments, private readonly OrderWorkflow $orders) {}

    public function current(?int $userId = null, string $register = 'caisse'): ?CashSession
    {
        return CashSession::where('company_id', company_id())->where('user_id', $userId ?? auth()->id())
            ->where('register', $register)->where('status', 'open')->latest('opened_at')->first();
    }

    public function open(FinancialAccount $account, float $openingAmount, string $register = 'caisse'): CashSession
    {
        if ($this->current(register: $register)) {
            throw new DomainException('Vous avez déjà une session ouverte.');
        }

        return CashSession::create([
            'company_id' => $account->company_id,
            'user_id' => auth()->id(),
            'financial_account_id' => $account->id,
            'reference' => $this->sequences->next('cash_session', $account->company_id),
            'register' => $register,
            'opened_at' => now(),
            'opening_amount' => $openingAmount,
            'expected_amount' => $openingAmount,
        ]);
    }

    /** Mouvements du compte de caisse pendant la session. */
    public function movements(CashSession $session)
    {
        return BankTransaction::where('financial_account_id', $session->financial_account_id)
            ->where('created_at', '>=', $session->opened_at)
            ->when($session->closed_at, fn ($q) => $q->where('created_at', '<=', $session->closed_at))
            ->latest('id');
    }

    public function expected(CashSession $session): float
    {
        return round((float) $session->opening_amount + (float) $this->movements($session)->sum('amount'), 2);
    }

    public function close(CashSession $session, float $counted, ?string $notes = null): CashSession
    {
        if ($session->status !== 'open') {
            throw new DomainException('Cette session est déjà clôturée.');
        }
        $session->update(['expected_amount' => $this->expected($session), 'closing_amount' => $counted, 'closed_at' => now(), 'status' => 'closed', 'notes' => $notes]);

        return $session;
    }

    /** Encaisse une facture depuis la caisse (espèces, mobile money, carte…). */
    public function collect(CashSession $session, Document $invoice, float $amount, string $method, ?string $externalReference = null): Payment
    {
        return $this->receive($invoice, $amount, $method, $session->account, $externalReference, "Encaissement caisse {$session->reference}");
    }

    /** Enregistre un règlement sur n'importe quel compte (virement bancaire, mobile money…). */
    public function receive(Document $invoice, float $amount, string $method, FinancialAccount $account, ?string $externalReference = null, ?string $notes = null): Payment
    {
        if ($invoice->type !== DocumentType::Invoice) {
            throw new DomainException('Seule une facture peut être encaissée.');
        }
        if ($amount <= 0 || $amount > $invoice->balance_due + 1e-6) {
            throw new DomainException('Montant invalide : solde restant '.money($invoice->balance_due, $invoice->currency_code).'.');
        }

        return DB::transaction(function () use ($account, $invoice, $amount, $method, $externalReference, $notes) {
            $payment = $this->payments->handle([
                'company_id' => $invoice->company_id,
                'contact_id' => $invoice->contact_id,
                'created_by' => auth()->id(),
                'reference' => 'ENC-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'direction' => 'in',
                'method' => $method,
                'account_type' => $account->type,
                'financial_account_id' => $account->id,
                'amount' => $amount,
                'currency_code' => $invoice->currency_code,
                'exchange_rate' => $invoice->exchange_rate,
                'paid_at' => now(),
                'external_reference' => $externalReference,
                'notes' => $notes ?? "Règlement sur {$account->name}",
            ], [['document_id' => $invoice->id, 'amount' => $amount]]);

            if ($order = SalesOrder::where('invoice_id', $invoice->id)->first()) {
                $this->orders->syncPayment($order);
            }

            return $payment;
        });
    }
}
