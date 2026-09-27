<?php

namespace App\Services\Support;

/**
 * Calcul des lignes et totaux (HT, remises, TVA, TTC) communs aux devis, commandes, ventes et factures.
 */
class TotalsCalculator
{
    /**
     * @param  array{quantity: float|int|string, unit_price: float|int|string, discount?: float|int|string|null, tax_rate?: float|int|string|null}  $line
     * @return array{subtotal: float, discount: float, taxable: float, tax: float, total: float}
     */
    public function line(array $line): array
    {
        $subtotal = round((float) $line['quantity'] * (float) $line['unit_price'], 2);
        $discount = min($subtotal, round((float) ($line['discount'] ?? 0), 2));
        $taxable = $subtotal - $discount;
        $tax = round($taxable * (float) ($line['tax_rate'] ?? 0) / 100, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxable' => $taxable,
            'tax' => $tax,
            'total' => round($taxable + $tax, 2),
        ];
    }

    /**
     * @param  iterable<array<string, mixed>>  $lines
     * @return array{subtotal: float, discount_total: float, tax_total: float, total: float}
     */
    public function document(iterable $lines, float $globalDiscount = 0): array
    {
        $totals = ['subtotal' => 0.0, 'discount_total' => 0.0, 'tax_total' => 0.0, 'total' => 0.0];

        foreach ($lines as $line) {
            $computed = $this->line($line);
            $totals['subtotal'] += $computed['subtotal'];
            $totals['discount_total'] += $computed['discount'];
            $totals['tax_total'] += $computed['tax'];
            $totals['total'] += $computed['total'];
        }

        $globalDiscount = min(round($globalDiscount, 2), $totals['total']);
        $totals['discount_total'] += $globalDiscount;
        $totals['total'] -= $globalDiscount;

        return array_map(fn (float $value): float => round($value, 2), $totals);
    }
}
