<?php

namespace App\Services\Cash;

use App\Enums\CashMovementType;
use App\Enums\OrderStatus;
use App\Events\PaymentRecorded;
use App\Exceptions\BusinessRuleException;
use App\Models\CashSession;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\OrderWorkflow;
use App\Services\Sales\ProductionRouter;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encaissement d'une commande d'imprimerie en caisse :
 * paiement(s) → facture → caisse → trésorerie → comptabilité → transfert Graphisme/Impression.
 */
class OrderCheckoutService
{
    public function __construct(
        private NumberGenerator $numbers,
        private InvoiceService $invoices,
        private TreasuryService $treasury,
        private CashSessionService $cash,
        private AccountingPoster $poster,
        private OrderWorkflow $workflow,
        private ProductionRouter $router,
        private PaymentAllocator $allocator,
    ) {}

    /**
     * @param  list<PaymentData>  $payments
     * @return array{order: Order, invoice: Invoice, payments: Collection<int, Payment>, change: float}
     */
    public function checkout(Order $order, array $payments, CashSession $session, User $cashier): array
    {
        if (! $cashier->can('cash.collect')) {
            throw new BusinessRuleException('Vous n’êtes pas autorisé à encaisser.');
        }

        $result = DB::transaction(function () use ($order, $payments, $session, $cashier): array {
            $order = Order::query()->with('client', 'items')->lockForUpdate()->findOrFail($order->id);
            $session->refresh();

            if (! $session->isOpen() || $session->user_id !== $cashier->id) {
                throw new BusinessRuleException('Aucune session de caisse ouverte à votre nom.');
            }

            if (in_array($order->status, [OrderStatus::Draft, OrderStatus::Cancelled, OrderStatus::Closed, OrderStatus::Suspended], true)) {
                throw new BusinessRuleException("La commande {$order->number} ne peut pas être encaissée (statut : {$order->status->label()}).");
            }

            $balance = $order->balanceDue();
            if ($balance <= 0) {
                throw new BusinessRuleException("La commande {$order->number} est déjà soldée.");
            }

            $thisOrderExposure = (float) $order->invoices()->where('status', '!=', 'cancelled')->get()->sum(fn (Invoice $invoice): float => $invoice->balanceDue());
            ['payments' => $cashable, 'credit' => $creditAmount, 'change' => $change] = $this->allocator->allocate($payments, $balance, $order->client, $thisOrderExposure);

            $invoice = $this->invoices->forOrder($order, $cashier);
            $batch = (string) Str::uuid();
            $register = $session->register;

            $recorded = collect($cashable)->map(function (PaymentData $line) use ($order, $invoice, $session, $register, $cashier, $batch): Payment {
                $treasuryAccount = $this->treasury->accountFor($line->method, $order->site_id, $register);

                $payment = Payment::query()->create([
                    'number' => $this->numbers->next(NumberGenerator::Payment),
                    'batch' => $batch,
                    'direction' => 'in',
                    'payable_type' => $order->getMorphClass(),
                    'payable_id' => $order->id,
                    'client_id' => $order->client_id,
                    'invoice_id' => $invoice->id,
                    'method' => $line->method,
                    'amount' => $line->amount,
                    'reference' => $line->reference,
                    'cash_session_id' => $session->id,
                    'treasury_account_id' => $treasuryAccount->id,
                    'site_id' => $order->site_id,
                    'received_by' => $cashier->id,
                    'paid_at' => now(),
                ]);

                $this->cash->record($session, CashMovementType::OrderPayment, 'in', $line->amount, $line->method, $payment, "Commande {$order->number}");
                $this->treasury->credit($treasuryAccount, $line->amount);
                $this->poster->postCustomerPayment($payment->setRelation('treasuryAccount', $treasuryAccount)->setRelation('payable', $order));
                $this->invoices->applyPayment($invoice, $line->amount);

                return $payment;
            });

            $order->update(['amount_paid' => round((float) $order->amount_paid + PaymentData::total($cashable), 2)]);

            $reachedDeposit = (float) $order->amount_paid + $creditAmount >= (float) $order->deposit_required;
            if ($order->status === OrderStatus::AwaitingPayment && $reachedDeposit) {
                $comment = $order->isFullyPaid() ? 'Paiement intégral' : ($creditAmount > 0 ? 'Crédit client autorisé' : 'Acompte encaissé');
                $this->workflow->transition($order, OrderStatus::Paid, $cashier, $comment);
                $this->router->route($order, $cashier);
            }

            return ['order' => $order->refresh(), 'invoice' => $invoice->refresh(), 'payments' => $recorded, 'change' => $change];
        });

        $result['payments']->each(fn (Payment $payment) => PaymentRecorded::dispatch($payment));

        return $result;
    }
}
