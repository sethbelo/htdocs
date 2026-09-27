<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Support\DisplayCurrency;
use App\Support\ReportPdfGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Exports the three management registers through the shared A4 document model. */
final class ManagementReportPdfController extends Controller
{
    public function __invoke(Request $request, ReportPdfGenerator $pdf)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['sales', 'stock', 'sessions'])],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'department' => ['nullable', 'string', 'max:40'],
        ]);

        $sales = collect();
        $movements = collect();
        $sessions = collect();
        $lowStockProducts = collect();
        $period = [Carbon::parse($validated['start'])->startOfDay(), Carbon::parse($validated['end'])->endOfDay()];

        if ($validated['type'] === 'sales') {
            $query = Sale::with(['customer', 'user'])
                ->whereBetween('created_at', $period);
            if (! empty($validated['department'])) {
                $query->where('department', $validated['department']);
            }
            $sales = $query->latest()->get();
        } elseif ($validated['type'] === 'stock') {
            $movements = StockMovement::with(['product', 'user'])
                ->whereBetween('created_at', $period)
                ->latest()->get();
            $lowStockProducts = Product::where('type', 'product')->whereRaw('stock_qty <= alert_qty')->orderBy('name')->get();
        } else {
            $sessions = RegisterSession::with(['user', 'sales'])
                ->whereBetween('opened_at', $period)
                ->latest()->get();
        }

        $filename = sprintf('Rapport-%s-%s-au-%s.pdf', $validated['type'], $validated['start'], $validated['end']);

        return $pdf->download('pdf.management-report', [
            'reportType' => $validated['type'],
            'startDate' => $validated['start'],
            'endDate' => $validated['end'],
            'department' => $validated['department'] ?? null,
            'sales' => $sales,
            'movements' => $movements,
            'sessions' => $sessions,
            'lowStockProducts' => $lowStockProducts,
            'company' => ['currency' => DisplayCurrency::code()],
        ], $filename, $request->user()?->name ?? 'Système');
    }
}
