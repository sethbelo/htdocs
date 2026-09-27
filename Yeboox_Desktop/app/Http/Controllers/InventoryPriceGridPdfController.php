<?php

namespace App\Http\Controllers;

use App\Support\DisplayCurrency;
use App\Support\PriceGridCatalog;
use App\Support\ReportPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class InventoryPriceGridPdfController extends Controller
{
    public function __invoke(Request $request, ReportPdfGenerator $pdf)
    {
        $validated = $request->validate([
            'template' => ['required', Rule::in(array_keys(PriceGridCatalog::templates()))],
            'category' => ['nullable', 'string', 'max:120'],
            'show_sku' => ['nullable', 'boolean'],
            'show_tax' => ['nullable', 'boolean'],
        ]);

        $templateKey = $validated['template'];
        $response = $pdf->download('pdf.inventory-price-grid', [
            'groups' => PriceGridCatalog::grouped($validated['category'] ?? null),
            'priceGridTemplate' => $templateKey,
            'priceGridTheme' => PriceGridCatalog::template($templateKey),
            'currency' => DisplayCurrency::baseCode(),
            'exchangeRate' => DisplayCurrency::rate(),
            'showSku' => (bool) ($validated['show_sku'] ?? true),
            'showTax' => (bool) ($validated['show_tax'] ?? true),
            'selectedCategory' => $validated['category'] ?? null,
        ], 'Grille-tarifaire-'.$templateKey.'-'.now()->format('Y-m-d').'.pdf', $request->user()?->name ?? 'Système', 'portrait');

        $response->headers->set('X-Yeboox-Price-Grid-Template', $templateKey);

        return $response;
    }
}
