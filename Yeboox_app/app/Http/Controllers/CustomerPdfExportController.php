<?php

namespace App\Http\Controllers;

use App\Services\CustomerPdfReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerPdfExportController extends Controller
{
    public function __invoke(Request $request, CustomerPdfReport $report): Response
    {
        abort_unless($request->user()->can('customers.export'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'loyalty' => ['nullable', 'in:standard,bronze,silver,gold,platinum'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        return $report->generate($request->user()->establishment, $filters)
            ->download('clients-'.now()->format('Y-m-d').'.pdf');
    }
}
