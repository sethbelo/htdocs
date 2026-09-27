<?php

namespace App\Services\Pos;

use App\Enums\CashMovementType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Events\SaleCompleted;
use App\Exceptions\BusinessRuleException;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\User;
use App\Services\Accounting\AccountingPoster;
use App\Services\Cash\CashSessionService;
use App\Services\Cash\PaymentAllocator;
use App\Services\Cash\PaymentData;
use App\Services\Cash\TreasuryService;
use App\Services\Crm\LoyaltyService;
use App\Services\Inventory\StockService;
use App\Services\Sales\InvoiceService;
use App\Services\Support\NumberGenerator;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Validation d'une vente boutique, en une seule transaction atomique :
 * 1. vente · 2. paiements · 3. déstockage · 4. mouvements d'inventaire · 5. caisse
 * 6. écritures comptables · 7. fidélité · 8. ticket (facture simplifiée prête à imprimer).
 */
class PosCheckoutService
{
    public function __construct(
        private NumberGenerator $numbers,
        private TotalsCalculator $calculator,
        private PaymentAllocator $allocator,
        private TreasuryService $treasury,
        private CashSessionService $cash,
        private StockService $stock,
        private AccountingPoster $poster,
        private LoyaltyService $loyalty,
        private InvoiceService $invoices,
    ) {}

    /**
     * @param  list<CartLine>  $cart
     * @param  list<PaymentData>  $payments
     */
    public function checkout(
        array $cart,
        array $payments,
        CashSession $session,
        User $cashier,
        ?Client $client = null,
        ?string $promotionCode = null,
        int $redeemPoints = 0,
    ): Sale {
        if ($cart === []) {
            throw new BusinessRuleException('Le panier est vide.');
        }

        $sale = DB::transaction(function () use ($cart, $payments, $session, $cashier, $client, $promotionCode, $redeemPoints): Sale {
            $session->refresh()->load('register.warehouse', 'register.treasuryAccount');
            $register = $session->register;

            if (! $session->isOpen() || $session->user_id !== $cashier->id) {
                throw new BusinessRuleException('Aucune session de caisse ouverte à votre nom.');
            }

            if (! $register->warehouse_id) {
                throw new BusinessRuleException("Aucun entrepôt n’est associé à la caisse {$register->code}.");
            }

            $products = Product::query()->whereIn('id', array_map(fn (CartLine $line): int => $line->productId, $cart))->get()->keyBy('id');
            $lines = $this->buildLines($cart, $products, $cashier);
            $totals = $this->calculator->document($lines);

            $promotion = $promotionCode ? Promotion::query()->active($register->site_id)->where('code', $promotionCode)->first() : null;
            if ($promotionCode && ! $promotion) {
                throw new BusinessRuleException('Code promotion invalide ou expiré.');
            }

            $globalDiscount = $promotion?->discountFor($totals['total']) ?? 0;

            if ($client && ($rate = (float) ($client->loyaltyAccount?->tier?->discount_rate ?? 0)) > 0) {
                $globalDiscount += round(($totals['total'] - $globalDiscount) * $rate / 100, 2);
            }

            $sale = Sale::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Sale),
                'site_id' => $register->site_id,
                'warehouse_id' => $register->warehouse_id,
                'cash_register_id' => $register->id,
                'cash_session_id' => $session->id,
                'client_id' => $client?->id,
                'promotion_id' => $promotion?->id,
                'user_id' => $cashier->id,
                'sold_at' => now(),
                'status' => SaleStatus::Completed,
            ]);

            if ($redeemPoints > 0) {
                if (! $client) {
                    throw new BusinessRuleException('Sélectionnez le client pour utiliser ses points.');
                }
                $globalDiscount += $this->loyalty->redeem($client, $redeemPoints, $sale);
            }

            $totals = $this->calculator->document($lines, $globalDiscount);

            ['payments' => $cashable, 'credit' => $credit, 'change' => $change, 'cashed' => $cashed] = $this->allocator->allocate($payments, $totals['total'], $client);

            if (round($cashed + $credit, 2) < $totals['total']) {
                throw new BusinessRuleException('Paiement insuffisant : il manque '.number_format($totals['total'] - $cashed - $credit, 2, ',', ' ').'.');
            }

            $sale->update($totals + ['amount_paid' => round($cashed + $change, 2), 'change_due' => $change]);

            foreach ($lines as $line) {
                $item = $sale->items()->create([
                    'product_id' => $line['product_id'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'unit_cost' => $line['unit_cost'],
                    'discount' => $line['discount'],
                    'tax_rate' => $line['tax_rate'],
                    'total' => $this->calculator->line($line)['total'],
                ]);

                $this->stock->move($products[$line['product_id']], $register->warehouse_id, StockMovementType::Sale, (float) $item->quantity, $sale, "Vente {$sale->number}", $cashier);
            }

            $batch = (string) Str::uuid();
            foreach ($cashable as $line) {
                $treasuryAccount = $this->treasury->accountFor($line->method, $register->site_id, $register);

                $payment = Payment::query()->create([
                    'number' => $this->numbers->next(NumberGenerator::Payment),
                    'batch' => $batch,
                    'direction' => 'in',
                    'payable_type' => $sale->getMorphClass(),
                    'payable_id' => $sale->id,
                    'client_id' => $client?->id,
                    'method' => $line->method,
                    'amount' => $line->amount,
                    'reference' => $line->reference,
                    'cash_session_id' => $session->id,
                    'treasury_account_id' => $treasuryAccount->id,
                    'site_id' => $register->site_id,
                    'received_by' => $cashier->id,
                    'paid_at' => now(),
                ]);

                $this->cash->record($session, CashMovementType::Sale, 'in', $line->amount, $line->method, $sale, "Vente {$sale->number}");
                $this->treasury->credit($treasuryAccount, $line->amount);
            }

            $sale->load('payments.treasuryAccount', 'items');
            $this->poster->postSale($sale);

            if ($client) {
                $sale->update(['loyalty_points_earned' => $this->loyalty->earn($client, (float) $sale->total, $sale)]);
            }

            $this->invoices->forSale($sale);

            return $sale->refresh();
        });

        SaleCompleted::dispatch($sale);

        return $sale;
    }

    /**
     * @param  list<CartLine>  $cart
     * @param  Collection<int, Product>  $products
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $cart, $products, User $cashier): array
    {
        return array_map(function (CartLine $line) use ($products, $cashier): array {
            $product = $products[$line->productId] ?? throw new BusinessRuleException('Article introuvable.');

            if (! $product->is_active) {
                throw new BusinessRuleException("L’article « {$product->name} » est désactivé.");
            }

            if ($line->quantity <= 0) {
                throw new BusinessRuleException("Quantité invalide pour « {$product->name} ».");
            }

            if ($line->unitPrice !== null && $line->unitPrice != (float) $product->sale_price && ! $cashier->can('pos.change_price')) {
                throw new BusinessRuleException('Modification de prix non autorisée.');
            }

            if ($line->discount > 0 && ! $cashier->can('pos.discount')) {
                throw new BusinessRuleException('Remise non autorisée.');
            }

            return [
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unitPrice ?? (float) $product->sale_price,
                'unit_cost' => (float) $product->cost_price,
                'discount' => $line->discount,
                'tax_rate' => (float) $product->tax_rate,
            ];
        }, $cart);
    }
}
