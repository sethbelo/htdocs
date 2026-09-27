<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\SyncOutbox;
use App\Models\UnitOfMeasure;
use App\Support\ApiAbilityCatalog;
use App\Support\DisplayCurrency;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenant): JsonResponse
    {
        return response()->json(['data' => [
            'api_version' => '1.0',
            'server_time' => now()->toISOString(),
            'synchronization' => [
                'mode' => config('offline-sync.runtime'),
                'enabled' => (bool) config('offline-sync.enabled'),
                'pending_sales' => SyncOutbox::query()
                    ->where('company_id', $tenant->companyId())
                    ->where('point_of_sale_id', $tenant->pointOfSaleId())
                    ->whereIn('status', ['pending', 'retry'])
                    ->count(),
                'last_success_at' => SyncOutbox::query()
                    ->where('company_id', $tenant->companyId())
                    ->where('point_of_sale_id', $tenant->pointOfSaleId())
                    ->where('status', 'synced')
                    ->max('synced_at'),
            ],
            'tenant' => [
                'company_id' => $tenant->companyId(),
                'establishment_id' => $tenant->establishmentId(),
                'point_of_sale_id' => $tenant->pointOfSaleId(),
                'company' => $tenant->company()?->only(['uuid', 'name', 'status']),
                'establishment' => $tenant->establishment()?->only(['uuid', 'name', 'code']),
                'point_of_sale' => $tenant->pointOfSale()?->only(['uuid', 'name', 'code', 'default_currency']),
                'subscription' => $tenant->pointOfSale()?->activeSubscription()?->only(['plan_code', 'status', 'starts_at', 'ends_at', 'features']),
            ],
            'company' => [
                'name' => Setting::get('company_name', 'Yeboox'),
                'address' => Setting::get('company_address', ''),
                'phone' => Setting::get('company_phone', ''),
                'logo_url' => ($logo = Setting::get('company_logo')) ? asset('storage/'.ltrim($logo, '/')) : asset('images/yeboox-logo.png'),
                'rccm' => Setting::get('company_rccm', ''),
                'nif' => Setting::get('company_nif', ''),
                'tva' => Setting::get('company_tva', ''),
            ],
            'currency' => [
                'base' => DisplayCurrency::baseCode(),
                'secondary' => DisplayCurrency::secondaryCode(),
                'exchange_rate' => DisplayCurrency::rate(),
                'rate_label' => DisplayCurrency::rateLabel(),
            ],
            'taxes' => [
                'default_rate' => (float) Setting::get('default_tax_rate', 16),
                'included_by_default' => filter_var(Setting::get('default_tax_included', '1'), FILTER_VALIDATE_BOOL),
            ],
            'abilities' => ApiAbilityCatalog::for($request->user()),
            'payment_methods' => PaymentMethod::query()->where('is_active', true)->orderBy('id')->get(['id', 'name', 'code', 'icon']),
            'payment_providers' => [
                'bank' => $this->settingList('pos_banks', ['Rawbank', 'EquityBCDC', 'Ecobank', 'TMB']),
                'mobile_money' => $this->settingList('pos_mobile_providers', ['M-Pesa', 'Airtel Money', 'Orange Money', 'Afrimoney']),
                'yepay' => $this->settingList('pos_yepay_providers', ['YePay Wallet']),
            ],
            'restaurant' => [
                'table_zones' => json_decode((string) Setting::get('restaurant_tables', '[]'), true) ?: [],
            ],
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'units' => UnitOfMeasure::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'symbol', 'type']),
        ]]);
    }

    private function settingList(string $key, array $fallback): array
    {
        $configured = json_decode((string) Setting::get($key, '[]'), true);

        return is_array($configured) && $configured !== [] ? array_values($configured) : $fallback;
    }
}
