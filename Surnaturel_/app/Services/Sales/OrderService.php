<?php

namespace App\Services\Sales;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quote;
use App\Models\User;
use App\Services\Support\NumberGenerator;
use App\Services\Support\TotalsCalculator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private NumberGenerator $numbers,
        private TotalsCalculator $calculator,
        private OrderWorkflow $workflow,
    ) {}

    /**
     * Création d'une commande par le commercial : numéro CMD-AAAA-NNNNNN, historique,
     * passage direct « En attente paiement » sauf si enregistrée en brouillon.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function create(array $data, array $items, User $salesRep, bool $asDraft = false): Order
    {
        return DB::transaction(function () use ($data, $items, $salesRep, $asDraft): Order {
            $order = Order::query()->create([
                'number' => $this->numbers->next(NumberGenerator::Order),
                'client_id' => $data['client_id'],
                'site_id' => $data['site_id'],
                'quote_id' => $data['quote_id'] ?? null,
                'sales_rep_id' => $salesRep->id,
                'ordered_at' => now(),
                'due_at' => $data['due_at'] ?? null,
                'priority' => $data['priority'] ?? OrderPriority::Normal,
                'status' => OrderStatus::Draft,
                'requires_design' => (bool) ($data['requires_design'] ?? true),
                'technical_notes' => $data['technical_notes'] ?? null,
                'deposit_required' => 0,
            ]);

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Draft,
                'user_id' => $salesRep->id,
                'comment' => 'Création de la commande',
                'created_at' => now(),
            ]);

            $this->syncItems($order, $items, (float) ($data['global_discount'] ?? 0), $data['deposit_rate'] ?? null);

            if (! $asDraft) {
                $this->workflow->transition($order, OrderStatus::AwaitingPayment, $salesRep, 'Commande transmise à la caisse');
            }

            return $order->refresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function syncItems(Order $order, array $items, float $globalDiscount = 0, float|int|string|null $depositRate = null): Order
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $line = $this->calculator->line($item);

            $order->items()->create(Arr::only($item, array_merge(
                ['product_id', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'specs'],
                OrderItem::SPEC_FIELDS,
            )) + ['total' => $line['total']]);
        }

        $totals = $this->calculator->document($items, $globalDiscount);
        $rate = $depositRate === null || $depositRate === '' ? null : (float) $depositRate;

        $order->update($totals + [
            'deposit_required' => $rate === null ? $totals['total'] : round($totals['total'] * $rate / 100, 2),
        ]);

        return $order;
    }

    /**
     * Devis accepté → commande (Devis → Commande → Facture → Encaissement).
     */
    public function createFromQuote(Quote $quote, User $salesRep, array $overrides = []): Order
    {
        $items = $quote->items->map(fn ($item): array => Arr::only($item->toArray(), array_merge(
            ['product_id', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'specs'],
            OrderItem::SPEC_FIELDS,
        )))->all();

        $order = $this->create($overrides + [
            'client_id' => $quote->client_id,
            'site_id' => $quote->site_id,
            'quote_id' => $quote->id,
        ], $items, $salesRep);

        $quote->update(['status' => QuoteStatus::Converted, 'order_id' => $order->id]);

        return $order;
    }
}
