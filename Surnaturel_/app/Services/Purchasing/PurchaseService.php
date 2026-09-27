<?php

namespace App\Services\Purchasing;

use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\GoodsReceipt;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingPoster;
use App\Services\Cash\TreasuryService;
use App\Services\Inventory\StockService;
use App\Services\Support\NumberGenerator;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demande d'achat → Validation → Bon de commande → Réception → Contrôle → Entrée stock → Facture → Paiement.
 */
class PurchaseService
{
    public function __construct(
        private NumberGenerator $numbers,
        private TotalsCalculator $calculator,
        private StockService $stock,
        private AccountingPoster $poster,
        private TreasuryService $treasury,
    ) {}

    /**
     * @param  list<array{product_id: int, description: string, quantity: float, unit_price: float, tax_rate?: float}>  $items
     */
    public function createOrder(Supplier $supplier, Warehouse $warehouse, array $items, User $user, ?PurchaseRequest $request = null, ?string $expectedOn = null): PurchaseOrder
    {
        if ($request && $request->status !== PurchaseRequestStatus::Approved) {
            throw new BusinessRuleException('La demande d’achat doit être validée avant commande.');
        }

        return DB::transaction(function () use ($supplier, $warehouse, $items, $user, $request, $expectedOn): PurchaseOrder {
            $totals = $this->calculator->document($items);

            $order = PurchaseOrder::query()->create([
                'number' => $this->numbers->next(NumberGenerator::PurchaseOrder),
                'supplier_id' => $supplier->id,
                'purchase_request_id' => $request?->id,
                'site_id' => $warehouse->site_id,
                'warehouse_id' => $warehouse->id,
                'status' => PurchaseOrderStatus::Sent,
                'ordered_on' => today(),
                'expected_on' => $expectedOn ?? today()->addDays($supplier->lead_time_days),
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'created_by' => $user->id,
                'approved_by' => $request ? $user->id : null,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item + ['total' => $this->calculator->line($item)['total']]);
            }

            $request?->update(['status' => PurchaseRequestStatus::Ordered]);

            return $order;
        });
    }

    /**
     * Réception contrôlée : seules les quantités acceptées entrent en stock.
     *
     * @param  array<int, array{accepted: float, rejected?: float, reason?: string|null}>  $quantities  indexé par purchase_order_item_id
     */
    public function receive(PurchaseOrder $order, array $quantities, User $user, ?string $notes = null): GoodsReceipt
    {
        if (in_array($order->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::Cancelled], true)) {
            throw new BusinessRuleException('Ce bon de commande est clôturé.');
        }

        return DB::transaction(function () use ($order, $quantities, $user, $notes): GoodsReceipt {
            $receipt = $order->receipts()->create([
                'number' => $this->numbers->next(NumberGenerator::GoodsReceipt),
                'warehouse_id' => $order->warehouse_id,
                'received_by' => $user->id,
                'controlled_by' => $user->id,
                'status' => 'posted',
                'received_at' => now(),
                'posted_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($order->items()->with('product')->get() as $item) {
                $accepted = (float) ($quantities[$item->id]['accepted'] ?? 0);
                $rejected = (float) ($quantities[$item->id]['rejected'] ?? 0);

                if ($accepted <= 0 && $rejected <= 0) {
                    continue;
                }

                if ($accepted > $item->remainingQuantity() + 0.0001) {
                    throw new BusinessRuleException("Quantité reçue supérieure au reliquat pour « {$item->description} ».");
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity_received' => $accepted + $rejected,
                    'quantity_accepted' => $accepted,
                    'quantity_rejected' => $rejected,
                    'rejection_reason' => $quantities[$item->id]['reason'] ?? null,
                ]);

                if ($accepted > 0) {
                    $item->increment('received_quantity', $accepted);
                    $this->stock->move($item->product, $order->warehouse_id, StockMovementType::PurchaseReceipt, $accepted, $receipt, "Réception {$receipt->number}", $user, (float) $item->unit_price);
                    $item->product->update(['cost_price' => $item->unit_price]);
                }
            }

            $fullyReceived = $order->items()->get()->every(fn ($item): bool => $item->remainingQuantity() <= 0);
            $order->update(['status' => $fullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived]);

            return $receipt;
        });
    }

    public function recordInvoice(PurchaseOrder $order, string $supplierReference, float $total, float $taxTotal, string $invoiceDate, ?string $dueDate = null): SupplierInvoice
    {
        return DB::transaction(function () use ($order, $supplierReference, $total, $taxTotal, $invoiceDate, $dueDate): SupplierInvoice {
            $invoice = SupplierInvoice::query()->create([
                'number' => $this->numbers->next(NumberGenerator::SupplierInvoice),
                'supplier_reference' => $supplierReference,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'site_id' => $order->site_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate ?? now()->parse($invoiceDate)->addDays($order->supplier->payment_terms_days)->toDateString(),
                'subtotal' => round($total - $taxTotal, 2),
                'tax_total' => $taxTotal,
                'total' => $total,
                'status' => 'pending',
            ]);

            $this->poster->postSupplierInvoice($invoice);

            return $invoice;
        });
    }

    public function pay(SupplierInvoice $invoice, TreasuryAccount $treasuryAccount, PaymentMethod $method, float $amount, User $user, ?string $reference = null): Payment
    {
        if ($amount <= 0 || $amount > $invoice->balanceDue() + 0.001) {
            throw new BusinessRuleException('Montant de règlement invalide.');
        }

        if (! $user->can('treasury.disburse')) {
            throw new BusinessRuleException('Vous n’êtes pas autorisé à décaisser.');
        }

        return DB::transaction(function () use ($invoice, $treasuryAccount, $method, $amount, $user, $reference): Payment {
            $payment = Payment::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Payment),
                'batch' => (string) Str::uuid(),
                'direction' => 'out',
                'payable_type' => $invoice->getMorphClass(),
                'payable_id' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'treasury_account_id' => $treasuryAccount->id,
                'site_id' => $invoice->site_id,
                'received_by' => $user->id,
                'paid_at' => now(),
            ]);

            $this->treasury->debit($treasuryAccount, $amount);
            $this->poster->postSupplierPayment($payment->setRelation('treasuryAccount', $treasuryAccount));

            $paid = round((float) $invoice->amount_paid + $amount, 2);
            $invoice->update(['amount_paid' => $paid, 'status' => $paid >= (float) $invoice->total ? 'paid' : 'partially_paid']);

            return $payment;
        });
    }
}
