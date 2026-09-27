<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Establishment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\PosSubscription;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Provisionne atomiquement une entreprise SaaS prête à encaisser. */
final class SaasProvisioner
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{company: Company, establishment: Establishment, point_of_sale: PointOfSale, administrator: User, subscription: PosSubscription}
     */
    public function createCompany(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $company = Company::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
                'legal_name' => $data['legal_name'] ?? $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'rccm' => $data['rccm'] ?? null,
                'nif' => $data['nif'] ?? null,
                'tva_number' => $data['tva_number'] ?? null,
                'base_currency' => $data['base_currency'] ?? 'USD',
                'secondary_currency' => $data['secondary_currency'] ?? 'CDF',
                'exchange_rate' => $data['exchange_rate'] ?? 2800,
                'timezone' => $data['timezone'] ?? 'Africa/Kinshasa',
                'status' => 'active',
            ]);

            $establishment = Establishment::create([
                'company_id' => $company->id,
                'uuid' => (string) Str::uuid(),
                'name' => $data['establishment_name'] ?? 'Établissement principal',
                'code' => 'ETAB-001',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'timezone' => $company->timezone,
                'is_active' => true,
            ]);

            $point = PointOfSale::create([
                'company_id' => $company->id,
                'establishment_id' => $establishment->id,
                'uuid' => (string) Str::uuid(),
                'name' => $data['point_of_sale_name'] ?? 'Point de vente principal',
                'code' => 'PDV-001',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'default_currency' => $company->base_currency,
                'timezone' => $company->timezone,
                'is_active' => true,
            ]);

            $subscription = PosSubscription::create([
                'company_id' => $company->id,
                'point_of_sale_id' => $point->id,
                'plan_code' => $data['plan_code'] ?? 'professional',
                'status' => $data['subscription_status'] ?? 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays((int) ($data['subscription_days'] ?? 30)),
                'amount' => $data['subscription_amount'] ?? 0,
                'currency' => $data['subscription_currency'] ?? 'USD',
                'billing_period' => $data['billing_period'] ?? 'monthly',
                'max_users' => $data['max_users'] ?? 10,
                'features' => $data['features'] ?? [
                    'pos' => true,
                    'inventory' => true,
                    'accounting' => true,
                    'reports' => true,
                    'api' => true,
                ],
            ]);

            $administrator = User::create([
                'company_id' => $company->id,
                'current_establishment_id' => $establishment->id,
                'current_point_of_sale_id' => $point->id,
                'name' => $data['administrator_name'],
                'email' => mb_strtolower($data['administrator_email']),
                'password' => Hash::make($data['administrator_password']),
                'email_verified_at' => now(),
                'is_company_admin' => true,
                'is_master_admin' => false,
                'is_active' => true,
            ]);
            $administrator->assignRole(Role::findOrCreate('Super administrateur', 'web'));
            $administrator->pointsOfSale()->attach($point->id, ['access_level' => 'administrator']);

            $this->context->run($company, $establishment, $point, function () use ($company): void {
                $this->seedPointOfSale($company);
            });

            return compact('company', 'establishment', 'administrator', 'subscription') + [
                'point_of_sale' => $point,
            ];
        }, 3);
    }

    public function seedPointOfSale(Company $company): void
    {
        UnitOfMeasure::updateOrCreate(
            ['symbol' => 'u'],
            ['name' => 'Unité', 'type' => 'unit', 'is_active' => true],
        );

        foreach ([
            'cash' => ['name' => 'Espèces', 'icon' => 'banknotes', 'syscohada_code' => '5711'],
            'bank' => ['name' => 'Banque', 'icon' => 'building-library', 'syscohada_code' => '5211'],
            'mobile_money' => ['name' => 'Mobile Money', 'icon' => 'device-phone-mobile', 'syscohada_code' => '5218'],
            'yepay' => ['name' => 'YePay', 'icon' => 'sparkles', 'syscohada_code' => '5218'],
        ] as $code => $attributes) {
            PaymentMethod::updateOrCreate(['code' => $code], [...$attributes, 'is_active' => true]);
        }

        ProductCategory::updateOrCreate(
            ['code' => 'GENERAL'],
            ['name' => 'Général', 'is_active' => true],
        );
        foreach ([
            'company_name' => $company->name,
            'company_email' => $company->email,
            'company_phone' => $company->phone,
            'company_address' => $company->address,
            'company_rccm' => $company->rccm,
            'company_nif' => $company->nif,
            'company_tva' => $company->tva_number,
            'company_currency' => $company->base_currency,
            'base_currency' => $company->base_currency,
            'secondary_currency' => $company->secondary_currency,
            'exchange_rate' => $company->exchange_rate,
            'currency_operation' => 'base_to_secondary',
            'default_tax_included' => '1',
            'default_tax_rate' => '16',
            'product_price_currency' => $company->base_currency,
            'receipt_template' => 'classic',
            'receipt_paper_width' => '80',
            'receipt_footer' => 'Merci de votre visite !',
            'pos_banks' => json_encode(['Rawbank', 'EquityBCDC', 'Ecobank', 'TMB']),
            'pos_mobile_providers' => json_encode(['M-Pesa', 'Airtel Money', 'Orange Money', 'Afrimoney']),
            'pos_yepay_providers' => json_encode(['YePay Wallet']),
            'restaurant_tables' => '[]',
        ] as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
