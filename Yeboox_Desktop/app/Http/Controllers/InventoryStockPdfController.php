<?php

namespace App\Http\Controllers;

use App\Support\DisplayCurrency;
use App\Support\InventoryStockReportData;
use App\Support\ReportPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class InventoryStockPdfController extends Controller
{
    public function __invoke(Request $request, ReportPdfGenerator $pdf)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['in_stock', 'alert', 'out'])],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $report = InventoryStockReportData::build(
            $validated['search'] ?? null,
            $validated['category'] ?? null,
            $validated['status'] ?? null,
            $validated['start'] ?? null,
            $validated['end'] ?? null,
        );
        $currency = DisplayCurrency::code();

        return $pdf->download('pdf.inventory-stock-report', [
            ...$report,
            'filters' => $validated,
            'currency' => $currency,
            'exchangeRate' => DisplayCurrency::rate(),
        ], 'Etat-des-stocks-'.now()->format('Y-m-d').'.pdf', $request->user()?->name ?? 'Système');
    }
}
