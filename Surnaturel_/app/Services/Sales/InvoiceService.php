<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Sale;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private NumberGenerator $numbers, private AccountingPoster $poster) {}

    /**
     * Facture définitive d'une commande, reliée au numéro de commande (référence métier principale).
     * Idempotent : une commande n'a qu'une facture active.
     */
    public function forOrder(Order $order, ?User $user = null): Invoice
    {
        $existing = $order->invoices()->where('type', InvoiceType::Invoice)->where('status', '!=', InvoiceStatus::Cancelled)->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($order, $user): Invoice {
            $invoice = Invoice::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Invoice),
                'type' => InvoiceType::Invoice,
                'order_id' => $order->id,
                'client_id' => $order->client_id,
                'site_id' => $order->site_id,
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'due_at' => now()->addDays((int) ($order->client?->allow_credit ? 30 : 0))->toDateString(),
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'tax_total' => $order->tax_total,
                'total' => $order->total,
                'amount_paid' => 0,
                'created_by' => $user?->id ?? auth()->id(),
                'validated_by' => $user?->id ?? auth()->id(),
                'validated_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => trim($item->description.' '.($item->specSummary() ? '— '.$item->specSummary() : '')),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax_rate' => $item->tax_rate,
                    'total' => $item->total,
                ]);
            }

            $this->poster->postCustomerInvoice($invoice->setRelation('order', $order));

            return $invoice;
        });
    }

    /**
     * Ticket/facture simplifiée d'une vente comptoir (déjà comptabilisée par la vente).
     */
    public function forSale(Sale $sale): Invoice
    {
        $invoice = Invoice::query()->create([
            'number' => $this->numbers->next(NumberGenerator::Invoice),
            'type' => InvoiceType::Invoice,
            'sale_id' => $sale->id,
            'client_id' => $sale->client_id,
            'site_id' => $sale->site_id,
            'status' => (float) $sale->amount_paid >= (float) $sale->total ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid,
            'issued_at' => $sale->sold_at,
            'subtotal' => $sale->subtotal,
            'discount_total' => $sale->discount_total,
            'tax_total' => $sale->tax_total,
            'total' => $sale->total,
            'amount_paid' => min((float) $sale->amount_paid, (float) $sale->total),
            'created_by' => $sale->user_id,
            'validated_by' => $sale->user_id,
            'validated_at' => now(),
        ]);

        foreach ($sale->items as $item) {
            $invoice->items()->create($item->only(['product_id', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'total']));
        }

        return $invoice;
    }

    public function applyPayment(Invoice $invoice, float $amount): Invoice
    {
        $paid = round((float) $invoice->amount_paid + $amount, 2);

        $invoice->update([
            'amount_paid' => $paid,
            'status' => $paid >= (float) $invoice->total ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid,
        ]);

        return $invoice;
    }
}
