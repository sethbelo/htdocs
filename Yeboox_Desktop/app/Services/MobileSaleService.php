<?php

namespace App\Services;

use App\Models\AccountingRecord;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\LoyaltyProgram;
use App\Support\DisplayCurrency;
use App\Services\Sync\OfflineSalePublisher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Encaisse une commande mobile en recalculant toutes les valeurs côté serveur. */
final class MobileSaleService
{
    /** @return array{sale: Sale, created: bool} */
    public function create(User $user, array $data): array
    {
        if ($existing = Sale::query()->where('client_reference', $data['client_reference'])->first()) {
            abort_unless($existing->user_id === $user->id, 409, 'Cette référence de commande est déjà utilisée.');

            return ['sale' => $this->load($existing), 'created' => false];
        }

        try {
            return DB::transaction(function () use ($user, $data): array {
                $session = RegisterSession::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->latest('opened_at')
                    ->first();

                if (! $session) {
                    throw ValidationException::withMessages(['register' => 'Ouvrez la caisse avant d’encaisser une vente.']);
                }

                $lines = $this->resolveLines($data['items']);
                $subtotal = round($lines->sum('total_price'), 2);
                $tax = round($lines->sum('tax_amount'), 2);
                $discount = round((float) ($data['discount_amount'] ?? 0), 2);
                if ($discount > $subtotal) {
                    throw ValidationException::withMessages(['discount_amount' => 'La remise ne peut pas dépasser le montant hors taxe.']);
                }

                $total = round($subtotal + $tax - $discount, 2);
                $payments = $this->resolvePayments($data['payments'], $total);
                $customer = isset($data['customer_id']) ? Customer::query()->where('is_active', true)->lockForUpdate()->findOrFail($data['customer_id']) : null;
                $earnedPoints = $customer && LoyaltyProgram::usesPoints() ? (int) floor($total) : 0;

                $sale = Sale::create([
                    'client_reference' => $data['client_reference'],
                    'source' => 'android',
                    'customer_id' => $customer?->id,
                    'user_id' => $user->id,
                    'register_session_id' => $session->id,
                    'department' => 'pos',
                    'fulfillment_type' => $data['fulfillment_type'],
                    'table_name' => $data['fulfillment_type'] === 'dine_in' ? ($data['table_name'] ?? null) : null,
                    'delivery_phone' => $data['fulfillment_type'] === 'delivery' ? ($data['delivery_phone'] ?? null) : null,
                    'delivery_address' => $data['fulfillment_type'] === 'delivery' ? ($data['delivery_address'] ?? null) : null,
                    'payment_method' => count($payments) > 1 ? 'multiple' : $payments[0]['method'],
                    'payment_reference' => collect($payments)->pluck('reference')->filter()->implode(', ') ?: null,
                    'total_amount' => $total,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'paid_amount' => $total,
                    'points_earned' => $earnedPoints,
                    'points_redeemed' => 0,
                ]);

                foreach ($lines as $line) {
                    $product = $line['product'];
                    $sale->items()->create(collect($line)->except('product')->all());
                    if ($product->type !== 'product') {
                        continue;
                    }

                    $before = (float) $product->stock_qty;
                    $after = $before - $line['quantity'];
                    if ($after < 0) {
                        throw ValidationException::withMessages(['items' => "Stock insuffisant pour {$product->name}."]);
                    }
                    $product->update(['stock_qty' => $after]);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'reason' => 'sale',
                        'reference' => 'API-POS-'.$sale->id,
                        'quantity' => $line['quantity'],
                        'unit_cost' => $product->cost,
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'description' => "Vente Android #{$sale->id}",
                        'user_id' => $user->id,
                    ]);
                }

                foreach ($payments as $payment) {
                    $method = PaymentMethod::query()->where('code', $payment['method'])->first();
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $method?->id,
                        'method' => $payment['method'],
                        'provider' => $payment['provider'] ?? null,
                        'reference' => $payment['reference'] ?? null,
                        'amount' => $payment['amount'],
                        'currency' => $payment['currency'],
                        'metadata' => [
                            'base_amount' => $payment['base_amount'],
                            'base_currency' => DisplayCurrency::baseCode(),
                            'secondary_currency' => DisplayCurrency::secondaryCode(),
                            'base_to_secondary_rate' => (float) ($session->base_to_secondary_rate ?: DisplayCurrency::rate()),
                            ...($payment['method'] === 'cash' ? ['received' => $payment['received'], 'change' => $payment['change']] : []),
                        ],
                    ]);
                }

                if ($customer && $earnedPoints > 0) {
                    $customer->increment('points', $earnedPoints);
                }

                $this->postAccounting($sale, $subtotal - $discount, $tax, $payments, $user);
                app(OfflineSalePublisher::class)->publish($sale);

                return ['sale' => $this->load($sale), 'created' => true];
            }, 3);
        } catch (QueryException $exception) {
            // Deux reprises réseau concurrentes peuvent franchir ensemble le
            // premier contrôle ; l'index unique choisit une seule écriture.
            $existing = Sale::query()->where('client_reference', $data['client_reference'])->first();
            if ($existing && $existing->user_id === $user->id) {
                return ['sale' => $this->load($existing), 'created' => false];
            }

            throw $exception;
        }
    }

    private function resolveLines(array $items)
    {
        return collect($items)->map(function (array $requested): array {
            $product = Product::query()->where('is_active', true)->lockForUpdate()->findOrFail($requested['product_id']);
            $quantity = round((float) $requested['quantity'], 3);
            $price = $product->netUnitPrice();
            $lineTotal = round($price * $quantity, 2);
            $lineTax = round($lineTotal * ((float) $product->sales_tax_rate / 100), 2);

            return [
                'product' => $product,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $price,
                'sales_tax_rate' => (float) $product->sales_tax_rate,
                'total_price' => $lineTotal,
                'tax_amount' => $lineTax,
            ];
        });
    }

    private function resolvePayments(array $payments, float $total): array
    {
        $resolved = collect($payments)->map(function (array $payment): array {
            $configuredMethod = PaymentMethod::query()->where('code', $payment['method'])->where('is_active', true)->first();
            if (! $configuredMethod) {
                throw ValidationException::withMessages(['payments' => "Le moyen de paiement {$payment['method']} est indisponible."]);
            }
            if ($payment['method'] !== 'cash' && (blank($payment['provider'] ?? null) || blank($payment['reference'] ?? null))) {
                throw ValidationException::withMessages(['payments' => 'Une banque ou un opérateur et une référence sont requis pour tout paiement électronique.']);
            }
            if (($payment['currency'] ?? null) !== DisplayCurrency::baseCode()) {
                throw ValidationException::withMessages(['payments' => 'Tous les paiements doivent utiliser la devise de base '.DisplayCurrency::baseCode().'.']);
            }
            $amount = round((float) $payment['amount'], DisplayCurrency::decimals());
            $base = $amount;
            $received = round((float) ($payment['received'] ?? $amount), DisplayCurrency::decimals());
            if ($payment['method'] === 'cash' && $received < $amount) {
                throw ValidationException::withMessages(['payments' => 'Le montant reçu est inférieur au paiement en espèces.']);
            }

            return [...$payment, 'amount' => $amount, 'base_amount' => $base, 'received' => $received, 'change' => max(0, $received - $amount)];
        })->all();

        $difference = round($total - (float) collect($resolved)->sum('base_amount'), 4);
        if (abs($difference) > 0.01) {
            throw ValidationException::withMessages(['payments' => 'La somme des paiements doit correspondre au total calculé par le serveur.']);
        }

        // Absorber l'arrondi de conversion sur la dernière ligne garantit une
        // écriture OHADA exactement équilibrée au centime comptable.
        $last = array_key_last($resolved);
        $resolved[$last]['base_amount'] = round($resolved[$last]['base_amount'] + $difference, 4);

        return $resolved;
    }

    private function postAccounting(Sale $sale, float $netRevenue, float $tax, array $payments, User $user): void
    {
        $common = [
            'reference' => 'API-POS-'.now()->format('Ymd').'-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT),
            'journal_code' => 'VE',
            'entry_type' => 'sale',
            'record_date' => today(),
            'description' => "Vente Android #{$sale->id}",
            'user_id' => $user->id,
            'sale_id' => $sale->id,
            'source_type' => $sale->getMorphClass(),
            'source_id' => $sale->id,
        ];

        foreach ($payments as $payment) {
            $method = PaymentMethod::query()->where('code', $payment['method'])->first();
            AccountingRecord::create([...$common, 'syscohada_code' => $method?->syscohada_code ?: '5711', 'debit' => $payment['base_amount'], 'credit' => 0]);
        }
        AccountingRecord::create([...$common, 'syscohada_code' => '701', 'debit' => 0, 'credit' => $netRevenue]);
        if ($tax > 0) {
            AccountingRecord::create([...$common, 'syscohada_code' => '4431', 'debit' => 0, 'credit' => $tax]);
        }
    }

    private function load(Sale $sale): Sale
    {
        return $sale->load(['customer', 'user', 'items.product', 'payments.paymentMethod']);
    }
}
