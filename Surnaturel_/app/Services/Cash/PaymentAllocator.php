<?php

namespace App\Services\Cash;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;

/**
 * Répartition d'un paiement mixte : rendu monnaie uniquement sur les espèces,
 * paiements électroniques plafonnés au montant dû, crédit client contrôlé.
 */
class PaymentAllocator
{
    /**
     * @param  list<PaymentData>  $payments
     * @param  float  $otherExposureExcluded  Encours déjà inclus dans le solde du client à ne pas compter deux fois.
     * @return array{payments: list<PaymentData>, credit: float, change: float, cashed: float}
     */
    public function allocate(array $payments, float $amountDue, ?Client $client = null, float $otherExposureExcluded = 0): array
    {
        $creditAmount = PaymentData::total(array_values(array_filter($payments, fn (PaymentData $p): bool => $p->method === PaymentMethod::Credit)));
        $others = array_values(array_filter($payments, fn (PaymentData $p): bool => $p->method !== PaymentMethod::Credit));

        if ($creditAmount > 0) {
            if (! $client?->allow_credit) {
                throw new BusinessRuleException('Le crédit n’est pas autorisé pour ce client.');
            }

            if ($client->outstandingBalance() - $otherExposureExcluded + $creditAmount > (float) $client->credit_limit) {
                throw new BusinessRuleException('Plafond de crédit client dépassé.');
            }
        }

        $nonCashTotal = PaymentData::total(array_values(array_filter($others, fn (PaymentData $p): bool => $p->method !== PaymentMethod::Cash)));
        $cashTotal = round(PaymentData::total($others) - $nonCashTotal, 2);

        if ($nonCashTotal + $creditAmount > $amountDue + 0.001) {
            throw new BusinessRuleException('Les paiements hors espèces dépassent le montant dû.');
        }

        $change = round(max(0, $cashTotal - max(0, $amountDue - $nonCashTotal - $creditAmount)), 2);
        $remainingChange = $change;
        $cashable = [];

        foreach ($others as $payment) {
            if ($payment->method === PaymentMethod::Cash && $remainingChange > 0) {
                $deducted = min($remainingChange, $payment->amount);
                $remainingChange = round($remainingChange - $deducted, 2);
                $payment = new PaymentData($payment->method, round($payment->amount - $deducted, 2), $payment->reference);
            }

            if ($payment->amount > 0) {
                $cashable[] = $payment;
            }
        }

        return ['payments' => $cashable, 'credit' => $creditAmount, 'change' => $change, 'cashed' => PaymentData::total($cashable)];
    }
}
