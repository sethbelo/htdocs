<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PointOfSale;
use App\Models\PosSubscription;
use App\Models\Product;
use App\Models\User;
use App\Services\SaasProvisioner;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Prépare un environnement SaaS local reproductible pour les tests manuels. */
class SaasDemoSeeder extends Seeder
{
    private const MASTER_EMAIL = 'master@yeboox.test';

    private const MASTER_PASSWORD = 'Yeboox-Master-2026!';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->deactivateLegacyUsers();
            $this->createMaster();

            $commerce = $this->createDemoCompany([
                'name' => 'Yeboox Démo Commerce',
                'slug' => 'yeboox-demo-commerce',
                'email' => 'contact.commerce@yeboox.test',
                'administrator_name' => 'Admin Démo Commerce',
                'administrator_email' => 'admin.commerce@yeboox.test',
                'administrator_password' => 'Commerce-Demo-2026!',
                'base_currency' => 'CDF',
                'secondary_currency' => 'USD',
                'exchange_rate' => 2850,
                'point_of_sale_name' => 'Boutique Gombe',
            ]);
            $this->seedCommerce($commerce);
            $this->addSecondCommercePoint($commerce);

            $services = $this->createDemoCompany([
                'name' => 'Yeboox Démo Services',
                'slug' => 'yeboox-demo-services',
                'email' => 'contact.services@yeboox.test',
                'administrator_name' => 'Admin Démo Services',
                'administrator_email' => 'admin.services@yeboox.test',
                'administrator_password' => 'Services-Demo-2026!',
                'base_currency' => 'USD',
                'secondary_currency' => 'CDF',
                'exchange_rate' => 2850,
                'point_of_sale_name' => 'Agence principale',
            ]);
            $this->seedServices($services);
        }, 3);
    }

    private function deactivateLegacyUsers(): void
    {
        User::query()
            ->whereNotIn('email', [
                self::MASTER_EMAIL,
                'admin.commerce@yeboox.test',
                'admin.services@yeboox.test',
            ])
            ->update([
                'is_active' => false,
                'is_master_admin' => false,
                'is_company_admin' => false,
            ]);
    }

    private function createMaster(): User
    {
        $master = User::query()->updateOrCreate(
            ['email' => self::MASTER_EMAIL],
            [
                'name' => 'Master Yeboox SaaS',
                'password' => Hash::make(self::MASTER_PASSWORD),
                'email_verified_at' => now(),
                'company_id' => null,
                'current_establishment_id' => null,
                'current_point_of_sale_id' => null,
                'is_master_admin' => true,
                'is_company_admin' => false,
                'is_active' => true,
            ],
        );
        $master->syncRoles([Role::findOrCreate('Super administrateur', 'web')]);
        $master->pointsOfSale()->detach();

        return $master;
    }

    /** @return array<string, mixed> */
    private function createDemoCompany(array $data): array
    {
        $existing = Company::query()->where('slug', $data['slug'])->first();
        if (! $existing) {
            return app(SaasProvisioner::class)->createCompany([
                ...$data,
                'plan_code' => 'enterprise',
                'subscription_status' => 'active',
                'subscription_days' => 365,
                'max_users' => 25,
            ]);
        }

        $existing->update(['status' => 'active']);
        $point = $existing->pointsOfSale()->with('establishment')->firstOrFail();
        $administrator = User::query()->where('email', $data['administrator_email'])->firstOrFail();
        $administrator->forceFill([
            'company_id' => $existing->id,
            'current_establishment_id' => $point->establishment_id,
            'current_point_of_sale_id' => $point->id,
            'password' => Hash::make($data['administrator_password']),
            'email_verified_at' => now(),
            'is_company_admin' => true,
            'is_master_admin' => false,
            'is_active' => true,
        ])->save();
        $administrator->syncRoles([Role::findOrCreate('Super administrateur', 'web')]);
        $administrator->pointsOfSale()->syncWithoutDetaching([$point->id => ['access_level' => 'administrator']]);
        $subscription = $point->subscriptions()->latest('ends_at')->firstOrFail();
        $subscription->update(['status' => 'active', 'ends_at' => now()->addYear()]);

        return [
            'company' => $existing,
            'establishment' => $point->establishment,
            'point_of_sale' => $point,
            'administrator' => $administrator,
            'subscription' => $subscription,
        ];
    }

    /** @param array<string, mixed> $tenant */
    private function seedCommerce(array $tenant): void
    {
        $this->runFor($tenant, function (): void {
            Product::query()->where('sku', 'DEFAULT-001')->update([
                'name' => 'Article divers', 'price' => 5000, 'stock_qty' => 100,
            ]);
            foreach ([
                ['Café premium 250 g', 'CAF-250', 'Épicerie', 18500, 40, 16],
                ['Eau minérale 1,5 L', 'EAU-150', 'Boissons', 2500, 120, 16],
                ['Jus naturel mangue', 'JUS-MAN', 'Boissons', 6500, 35, 16],
                ['Sac réutilisable', 'SAC-ECO', 'Accessoires', 3000, 75, 0],
            ] as [$name, $sku, $category, $price, $stock, $tax]) {
                Product::query()->updateOrCreate(['sku' => $sku], [
                    'name' => $name,
                    'category' => $category,
                    'type' => 'product',
                    'price' => $price,
                    'cost' => round($price * 0.62, 2),
                    'stock_qty' => $stock,
                    'alert_qty' => 10,
                    'sales_tax_rate' => $tax,
                    'tax_included' => true,
                    'is_active' => true,
                ]);
            }
            Customer::query()->updateOrCreate(['email' => 'client.commerce@example.test'], [
                'name' => 'Client Démo Commerce',
                'phone' => '+243810000101',
                'points' => 120,
                'is_active' => true,
            ]);
        });
    }

    /** @param array<string, mixed> $tenant */
    private function seedServices(array $tenant): void
    {
        $this->runFor($tenant, function (): void {
            Product::query()->where('sku', 'DEFAULT-001')->update([
                'name' => 'Prestation libre', 'price' => 10, 'type' => 'service',
            ]);
            foreach ([
                ['Consultation standard', 'SRV-CONS', 'Conseil', 25],
                ['Installation sur site', 'SRV-INST', 'Technique', 80],
                ['Abonnement mensuel', 'SRV-ABO', 'Abonnements', 45],
                ['Formation équipe', 'SRV-FORM', 'Formation', 120],
            ] as [$name, $sku, $category, $price]) {
                Product::query()->updateOrCreate(['sku' => $sku], [
                    'name' => $name,
                    'category' => $category,
                    'type' => 'service',
                    'price' => $price,
                    'cost' => round($price * 0.30, 2),
                    'stock_qty' => 0,
                    'alert_qty' => 0,
                    'sales_tax_rate' => 16,
                    'tax_included' => true,
                    'is_active' => true,
                ]);
            }
            Customer::query()->updateOrCreate(['email' => 'client.services@example.test'], [
                'name' => 'Client Démo Services',
                'phone' => '+243810000202',
                'points' => 50,
                'is_active' => true,
            ]);
        });
    }

    /** @param array<string, mixed> $tenant */
    private function addSecondCommercePoint(array $tenant): void
    {
        $company = $tenant['company'];
        $establishment = $tenant['establishment'];
        $point = PointOfSale::query()->firstOrCreate(
            ['establishment_id' => $establishment->id, 'code' => 'PDV-002'],
            [
                'company_id' => $company->id,
                'uuid' => (string) Str::uuid(),
                'name' => 'Boutique Limete',
                'default_currency' => 'CDF',
                'timezone' => 'Africa/Kinshasa',
                'is_active' => true,
            ],
        );
        PosSubscription::query()->updateOrCreate(
            ['point_of_sale_id' => $point->id, 'plan_code' => 'enterprise-demo'],
            [
                'company_id' => $company->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'amount' => 0,
                'currency' => 'USD',
                'billing_period' => 'annual',
                'max_users' => 25,
                'features' => ['pos' => true, 'inventory' => true, 'accounting' => true, 'reports' => true, 'api' => true],
            ],
        );
        $tenant['administrator']->pointsOfSale()->syncWithoutDetaching([$point->id => ['access_level' => 'administrator']]);

        app(TenantContext::class)->run($company, $establishment, $point, function () use ($company): void {
            app(SaasProvisioner::class)->seedPointOfSale($company);
            Product::query()->where('sku', 'DEFAULT-001')->update([
                'name' => 'Article divers Limete', 'price' => 5000, 'stock_qty' => 50,
            ]);
        });
    }

    /** @param array<string, mixed> $tenant */
    private function runFor(array $tenant, callable $callback): void
    {
        app(TenantContext::class)->run(
            $tenant['company'],
            $tenant['establishment'],
            $tenant['point_of_sale'],
            $callback,
        );
    }
}
