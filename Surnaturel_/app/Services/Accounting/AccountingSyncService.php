<?php

namespace App\Services\Accounting;

use App\Models\CashSession;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Synchronisation des points de vente avec la comptabilité générale :
 * contrôle que chaque vente et chaque encaissement de commande a son écriture, et passe celles qui manquent.
 */
class AccountingSyncService
{
    public function __construct(private AccountingPoster $poster) {}

    /**
     * @return array{sales: Collection<int, Sale>, payments: Collection<int, Payment>}
     */
    public function missing(?CashSession $session = null): array
    {
        $posted = fn (string $type): Builder => JournalEntry::query()->select('source_id')->where('source_type', $type);

        return [
            'sales' => Sale::query()->with('payments.treasuryAccount', 'items')
                ->when($session, fn (Builder $q) => $q->where('cash_session_id', $session->id))
                ->where('status', 'completed')
                ->whereNotIn('id', $posted('sale'))
                ->get(),
            'payments' => Payment::query()->with('treasuryAccount', 'payable')
                ->when($session, fn (Builder $q) => $q->where('cash_session_id', $session->id))
                ->where('direction', 'in')
                ->where('payable_type', 'order')
                ->whereNotIn('id', $posted('payment'))
                ->get(),
        ];
    }

    /**
     * Passe les écritures manquantes. Retourne le nombre d'écritures créées.
     */
    public function sync(?CashSession $session = null): int
    {
        $missing = $this->missing($session);

        return DB::transaction(function () use ($missing): int {
            foreach ($missing['sales'] as $sale) {
                $this->poster->postSale($sale);
            }

            foreach ($missing['payments'] as $payment) {
                $this->poster->postCustomerPayment($payment);
            }

            return $missing['sales']->count() + $missing['payments']->count();
        });
    }

    /**
     * @return array{entries: int, debit: float, sales_total: float, collected: float, missing: int, synced: bool}
     */
    public function status(CashSession $session): array
    {
        $saleIds = $session->sales()->pluck('id');
        $paymentIds = $session->payments()->where('payable_type', 'order')->pluck('id');

        $entries = JournalEntry::query()
            ->where(fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('source_type', 'sale')->whereIn('source_id', $saleIds))
                ->orWhere(fn (Builder $w) => $w->where('source_type', 'payment')->whereIn('source_id', $paymentIds)));

        $missing = $this->missing($session);
        $missingCount = $missing['sales']->count() + $missing['payments']->count();

        return [
            'entries' => (clone $entries)->count(),
            'debit' => round((float) (clone $entries)->sum('total_debit'), 2),
            'sales_total' => round((float) $session->sales()->where('status', 'completed')->sum('total'), 2),
            'collected' => round((float) $session->payments()->where('direction', 'in')->sum('amount'), 2),
            'missing' => $missingCount,
            'synced' => $missingCount === 0,
        ];
    }
}
