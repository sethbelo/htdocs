<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class SalesReportData
{
    public static function query(string $startDate, string $endDate, ?string $category = null): Builder
    {
        return Sale::query()
            ->with(['customer', 'user', 'payments', 'items.product'])
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->when($category, fn (Builder $query, string $value) => $query->whereHas(
                'items.product',
                fn (Builder $productQuery) => $productQuery->where('category', $value),
            ));
    }

    public static function build(string $startDate, string $endDate, ?string $category = null): array
    {
        $sales = self::query($startDate, $endDate, $category)->latest()->get();
        $rows = collect();

        foreach ($sales as $sale) {
            $eligibleItems = $sale->items
                ->filter(fn ($item) => ! $category || ($item->product?->category ?? 'Sans catégorie') === $category)
                ->values();

            if ($eligibleItems->isEmpty()) {
                continue;
            }

            $allItemsGross = max(0.01, (float) $sale->items->sum('total_price'));
            $eligibleTaxTotal = 0.0;
            $eligibleDiscountTotal = 0.0;
            $lastIndex = $eligibleItems->count() - 1;

            foreach ($eligibleItems as $index => $item) {
                $gross = (float) $item->total_price;
                $share = $gross / $allItemsGross;
                $tax = round((float) $sale->tax_amount * $share, 2);
                $discount = round((float) $sale->discount_amount * $share, 2);

                if (! $category && $index === $lastIndex) {
                    $tax = round((float) $sale->tax_amount - $eligibleTaxTotal, 2);
                    $discount = round((float) $sale->discount_amount - $eligibleDiscountTotal, 2);
                }

                $eligibleTaxTotal += $tax;
                $eligibleDiscountTotal += $discount;
                $netBeforeTax = round($gross - $discount, 2);
                $revenue = round($netBeforeTax + $tax, 2);
                $cost = round((float) ($item->product?->cost ?? 0) * (float) $item->quantity, 2);

                $rows->push([
                    'sale_id' => $sale->id,
                    'reference' => 'VTE-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                    'date' => $sale->created_at,
                    'cashier' => $sale->user?->name ?? 'Utilisateur supprimé',
                    'customer' => $sale->customer?->name ?? 'Client de passage',
                    'payment' => $sale->payment_method,
                    'category' => trim((string) ($item->product?->category ?? '')) ?: 'Sans catégorie',
                    'product' => $item->product?->name ?? 'Article supprimé',
                    'quantity' => (float) $item->quantity,
                    'net_before_tax' => $netBeforeTax,
                    'tax' => $tax,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => round($netBeforeTax - $cost, 2),
                ]);
            }
        }

        $categories = $rows
            ->groupBy('category')
            ->map(function (Collection $categoryRows, string $name) use ($rows): array {
                $netBeforeTax = round((float) $categoryRows->sum('net_before_tax'), 2);
                $revenue = round((float) $categoryRows->sum('revenue'), 2);
                $profit = round((float) $categoryRows->sum('profit'), 2);
                $totalRevenue = max(0.01, (float) $rows->sum('revenue'));

                return [
                    'name' => $name,
                    'orders' => $categoryRows->pluck('sale_id')->unique()->count(),
                    'quantity' => round((float) $categoryRows->sum('quantity'), 3),
                    'net_before_tax' => $netBeforeTax,
                    'tax' => round((float) $categoryRows->sum('tax'), 2),
                    'revenue' => $revenue,
                    'cost' => round((float) $categoryRows->sum('cost'), 2),
                    'profit' => $profit,
                    'margin' => $netBeforeTax > 0 ? round(($profit / $netBeforeTax) * 100, 1) : 0,
                    'share' => round(($revenue / $totalRevenue) * 100, 1),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        $netBeforeTax = round((float) $rows->sum('net_before_tax'), 2);
        $profit = round((float) $rows->sum('profit'), 2);
        $totals = [
            'orders' => $rows->pluck('sale_id')->unique()->count(),
            'quantity' => round((float) $rows->sum('quantity'), 3),
            'net_before_tax' => $netBeforeTax,
            'tax' => round((float) $rows->sum('tax'), 2),
            'revenue' => round((float) $rows->sum('revenue'), 2),
            'cost' => round((float) $rows->sum('cost'), 2),
            'profit' => $profit,
            'margin' => $netBeforeTax > 0 ? round(($profit / $netBeforeTax) * 100, 1) : 0,
        ];
        $totals['average_order'] = $totals['orders'] > 0 ? round($totals['revenue'] / $totals['orders'], 2) : 0;

        $trend = $rows
            ->groupBy(fn (array $row) => $row['date']->format('Y-m-d'))
            ->sortKeys()
            ->map(fn (Collection $dayRows, string $date) => [
                'date' => $date,
                'label' => $dayRows->first()['date']->translatedFormat('d M'),
                'revenue' => round((float) $dayRows->sum('revenue'), 2),
                'profit' => round((float) $dayRows->sum('profit'), 2),
            ])->values();

        return compact('sales', 'rows', 'categories', 'totals', 'trend');
    }
}
