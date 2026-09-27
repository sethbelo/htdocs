<?php

namespace App\Services;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Models\BankTransaction;
use App\Models\CashSession;
use App\Models\Contact;
use App\Models\PosOrder;
use App\Models\Product;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Point de vente retail : ticket, sortie de stock showroom, fidélité,
 * encaissement dans la session et écriture comptable de vente.
 */
class PosService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly StockService $stock,
        private readonly LoyaltyService $loyalty,
        private readonly SynchronizeAccounting $accounting,
    ) {}

    /**
     * @param  array  $cart  [['product_id', 'quantity', 'unit_price', 'discount_rate']]
     */
    public function checkout(CashSession $session, array $cart, ?Contact $customer, string $method, float $tendered, int $redeemPoints = 0): PosOrder
    {
        if ($session->status !== 'open') {
            throw new DomainException('Ouvrez une session de caisse avant de vendre.');
        }
        if (! $cart) {
            throw new DomainException('Le panier est vide.');
        }

        return DB::transaction(function () use ($session, $cart, $customer, $method, $tendered, $redeemPoints) {
            $warehouse = Warehouse::where('company_id', $session->company_id)->where('type', 'showroom')->first()
                ?? Warehouse::where('company_id', $session->company_id)->first();
            $card = $customer ? $this->loyalty->cardFor($customer) : null;
            $tierDiscount = $card ? $this->loyalty->tierDiscount($card) : 0;

            $subtotal = $discount = $tax = 0;
            $lines = [];
            foreach ($cart as $row) {
                $product = Product::where('company_id', $session->company_id)->findOrFail($row['product_id']);
                $qty = (float) $row['quantity'];
                $gross = round($qty * (float) $row['unit_price'], 2);
                $rate = max((float) ($row['discount_rate'] ?? 0), $tierDiscount);
                $lineDiscount = round($gross * $rate / 100, 2);
                $lineTax = round(($gross - $lineDiscount) * (float) $product->tax_rate / 100, 2);
                $subtotal += $gross;
                $discount += $lineDiscount;
                $tax += $lineTax;
                $lines[] = ['product' => $product, 'name' => $product->name, 'quantity' => $qty, 'unit_price' => $row['unit_price'], 'discount_rate' => $rate, 'tax_rate' => $product->tax_rate, 'total' => $gross - $lineDiscount + $lineTax];
            }

            $order = PosOrder::create([
                'company_id' => $session->company_id,
                'cash_session_id' => $session->id,
                'contact_id' => $customer?->id,
                'warehouse_id' => $warehouse?->id,
                'number' => $this->sequences->next('pos_order', $session->company_id),
                'payment_method' => $method,
            ]);

            $pointsValue = ($card && $redeemPoints > 0) ? $this->loyalty->redeem($card, $redeemPoints, $order) : 0;
            $total = round($subtotal - $discount + $tax - $pointsValue, 2);
            if ($method === 'cash' && $tendered + 1e-6 < $total) {
                throw new DomainException('Montant reçu insuffisant : '.money($total).' à encaisser.');
            }

            foreach ($lines as $line) {
                $product = $line['product'];
                unset($line['product']);
                $order->lines()->create($line + ['product_id' => $product->id]);
                if ($product->track_stock && $warehouse) {
                    $this->stock->issue($product, $warehouse, (float) $line['quantity'], 'sale', $order, $order->number, allowNegative: true);
                }
            }

            $earned = $card ? $this->loyalty->earn($card, $total, $order) : 0;
            $order->update([
                'subtotal' => $subtotal, 'discount' => round($discount + $pointsValue, 2), 'tax' => $tax, 'total' => $total,
                'tendered' => $method === 'cash' ? $tendered : $total, 'change_due' => $method === 'cash' ? round($tendered - $total, 2) : 0,
                'points_earned' => $earned, 'points_redeemed' => $redeemPoints,
            ]);

            $transaction = BankTransaction::create([
                'financial_account_id' => $session->financial_account_id, 'transaction_date' => now()->toDateString(), 'reference' => $order->number,
                'description' => "Vente comptoir {$order->number}", 'amount' => $total, 'status' => 'reconciled',
                'reconcilable_type' => $order->getMorphClass(), 'reconcilable_id' => $order->id,
            ]);
            $treasury = match ($session->account->type) {
                'cash' => '571', 'mobile_money' => '581', default => '521'
            };
            $this->accounting->postLines($transaction, 'CA', now()->toDateString(), "Vente comptoir {$order->number}", array_values(array_filter([
                [$treasury, 'Encaissement '.$order->number, $total, 0, $customer?->id],
                ['701', 'Ventes showroom', 0, round($total - $tax, 2), $customer?->id],
                $tax > 0 ? ['4431', 'TVA collectée', 0, $tax] : null,
            ])), $session->company_id);

            return $order->load('lines');
        });
    }
}
