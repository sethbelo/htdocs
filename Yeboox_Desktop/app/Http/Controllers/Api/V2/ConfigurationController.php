<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\DisplayCurrency;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigurationController extends Controller
{
    public function show(TenantContext $tenant): JsonResponse
    {
        return response()->json(['data' => [
            'company' => $tenant->company(),
            'establishment' => $tenant->establishment(),
            'point_of_sale' => $tenant->pointOfSale(),
            'currency' => [
                'base' => DisplayCurrency::baseCode(),
                'secondary' => DisplayCurrency::secondaryCode(),
                'exchange_rate' => DisplayCurrency::rate(),
                'operation' => 'base_to_secondary',
                'product_price_currency' => DisplayCurrency::baseCode(),
            ],
            'taxes' => [
                'default_rate' => (float) Setting::get('default_tax_rate', 16),
                'included_by_default' => filter_var(Setting::get('default_tax_included', '1'), FILTER_VALIDATE_BOOL),
                'company_tva_number' => Setting::get('company_tva', ''),
            ],
        ]]);
    }

    public function update(Request $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:190'],
            'company_email' => ['nullable', 'email', 'max:190'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_rccm' => ['nullable', 'string', 'max:100'],
            'company_nif' => ['nullable', 'string', 'max:100'],
            'company_tva' => ['nullable', 'string', 'max:100'],
            'company_currency' => ['sometimes', Rule::in([DisplayCurrency::baseCode()])],
            'secondary_currency' => ['sometimes', Rule::in(DisplayCurrency::SUPPORTED), 'different:company_currency'],
            'exchange_rate' => ['sometimes', 'numeric', 'gt:0'],
            'currency_operation' => ['sometimes', Rule::in(['base_to_secondary'])],
            'product_price_currency' => ['sometimes', Rule::in([DisplayCurrency::baseCode()])],
            'default_tax_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'default_tax_included' => ['sometimes', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }
        Setting::set('currency_operation', 'base_to_secondary');
        Setting::set('product_price_currency', DisplayCurrency::baseCode());

        $company = $tenant->company();
        $company?->update([
            'name' => $data['company_name'] ?? $company->name,
            'email' => $data['company_email'] ?? $company->email,
            'phone' => $data['company_phone'] ?? $company->phone,
            'address' => $data['company_address'] ?? $company->address,
            'rccm' => $data['company_rccm'] ?? $company->rccm,
            'nif' => $data['company_nif'] ?? $company->nif,
            'tva_number' => $data['company_tva'] ?? $company->tva_number,
            'base_currency' => $data['company_currency'] ?? $company->base_currency,
            'secondary_currency' => $data['secondary_currency'] ?? $company->secondary_currency,
            'exchange_rate' => $data['exchange_rate'] ?? $company->exchange_rate,
        ]);

        return response()->json(['message' => 'Configuration du point de vente mise à jour.', 'data' => $this->show($tenant)->getData(true)['data']]);
    }
}
