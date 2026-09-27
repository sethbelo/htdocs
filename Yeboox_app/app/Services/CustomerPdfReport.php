<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Establishment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class CustomerPdfReport
{
    public function generate(Establishment $establishment, array $filters = [])
    {
        $customers = Customer::query()
            ->forEstablishment($establishment->id)
            ->with('loyaltyCard')
            ->search($filters['search'] ?? null)
            ->when($filters['loyalty'] ?? null, fn (Builder $query, string $level) => $query->where('loyalty_level', $level))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $template = $establishment->getSetting('pdf_template', 'modern');
        if (! in_array($template, ['modern', 'classic', 'compact'], true)) {
            $template = 'modern';
        }

        $logoData = null;
        if ($establishment->logo_path && is_file(storage_path('app/public/'.$establishment->logo_path))) {
            $path = storage_path('app/public/'.$establishment->logo_path);
            $logoData = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
        }

        return Pdf::loadView('reports.customers', compact('customers', 'template', 'establishment', 'logoData'))
            ->setPaper('a4', 'landscape');
    }
}
