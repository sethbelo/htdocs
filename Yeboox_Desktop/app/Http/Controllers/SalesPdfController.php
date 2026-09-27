<?php

namespace App\Http\Controllers;

use App\Support\DisplayCurrency;
use App\Support\ReportPdfGenerator;
use App\Support\SalesReportData;
use Illuminate\Http\Request;

class SalesPdfController extends Controller
{
    public function __invoke(Request $request, ReportPdfGenerator $pdf)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $analysis = SalesReportData::build(
            $validated['start'],
            $validated['end'],
            $validated['category'] ?? null,
        );
        $filename = sprintf('Rapport-ventes-%s-au-%s.pdf', $validated['start'], $validated['end']);

        return $pdf->download('pdf.sales-report', [
            ...$analysis,
            'startDate' => $validated['start'],
            'endDate' => $validated['end'],
            'selectedCategory' => $validated['category'] ?? null,
            'company' => [
                'currency' => DisplayCurrency::code(),
            ],
        ], $filename, $request->user()?->name ?? 'Système');
    }
}
