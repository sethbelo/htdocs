<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\SaasProvisioner;
use App\Support\ApiAbilityCatalog;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaasTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_and_route_binding_are_isolated_between_companies(): void
    {
        $alpha = $this->provision('Alpha Commerce', 'admin.alpha@example.test');
        $beta = $this->provision('Beta Services', 'admin.beta@example.test');

        $alphaProduct = $this->createProductFor($alpha, 'ALPHA-SECRET');
        $betaProduct = $this->createProductFor($beta, 'BETA-SECRET');
        $token = $this->tenantToken($alpha['administrator'], $alpha['company']->id, $alpha['point_of_sale']->id);

        $this->withToken($token)->getJson('/api/v2/products')
            ->assertOk()
            ->assertJsonFragment(['sku' => $alphaProduct->sku])
            ->assertJsonMissing(['sku' => $betaProduct->sku]);

        $this->withToken($token)->getJson("/api/v2/products/{$betaProduct->id}")
            ->assertNotFound();
    }

    public function test_an_expired_point_of_sale_subscription_blocks_business_api_only(): void
    {
        $tenant = $this->provision('Gamma Market', 'admin.gamma@example.test');
        $tenant['subscription']->update(['status' => 'expired', 'ends_at' => now()->subDay()]);
        $token = $this->tenantToken($tenant['administrator'], $tenant['company']->id, $tenant['point_of_sale']->id);

        $this->withToken($token)->getJson('/api/v2/context')->assertOk();
        $this->withToken($token)->getJson('/api/v2/products')
            ->assertStatus(402)
            ->assertJsonFragment(['message' => 'L’abonnement de ce point de vente est expiré ou suspendu.']);
    }

    public function test_master_administrator_can_provision_a_complete_company_through_api_v2(): void
    {
        $master = User::factory()->create(['is_master_admin' => true, 'is_active' => true]);
        $master->assignRole(Role::firstOrCreate(['name' => 'Super administrateur', 'guard_name' => 'web']));
        $token = $master->createToken('Console master', ['*'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v2/master/companies', [
            'name' => 'Delta SARL',
            'administrator_name' => 'Admin Delta',
            'administrator_email' => 'admin.delta@example.test',
            'administrator_password' => 'MotDePasse-2026',
            'administrator_password_confirmation' => 'MotDePasse-2026',
            'base_currency' => 'CDF',
            'secondary_currency' => 'USD',
            'exchange_rate' => 2850,
            'subscription_days' => 90,
        ])->assertCreated()
            ->assertJsonPath('data.company.name', 'Delta SARL')
            ->assertJsonPath('data.point_of_sale.code', 'PDV-001')
            ->assertJsonPath('data.administrator.is_company_admin', true);

        $company = Company::query()->where('name', 'Delta SARL')->firstOrFail();
        $this->assertCount(1, $company->establishments);
        $this->assertCount(1, $company->pointsOfSale);
        $this->assertDatabaseHas('pos_subscriptions', [
            'company_id' => $company->id,
            'point_of_sale_id' => $company->pointsOfSale->first()->id,
        ]);
    }

    public function test_master_console_is_reserved_and_renders_the_saas_portfolio(): void
    {
        $master = User::factory()->create(['is_master_admin' => true, 'is_active' => true]);
        $master->assignRole(Role::firstOrCreate(['name' => 'Super administrateur', 'guard_name' => 'web']));

        $this->actingAs($master)->get(route('master.companies'))
            ->assertOk()
            ->assertSee('Plateforme SaaS')
            ->assertSee('Portefeuille d’entreprises');

        $ordinary = User::factory()->create(['is_master_admin' => false, 'is_active' => true]);
        $this->actingAs($ordinary)->get(route('master.companies'))->assertForbidden();
    }

    public function test_a_business_write_without_tenant_context_is_rejected_once_saas_is_multi_company(): void
    {
        $this->provision('Epsilon', 'admin.epsilon@example.test');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('contexte entreprise');
        Product::query()->create([
            'name' => 'Orphelin interdit',
            'sku' => 'ORPHAN-001',
            'category' => 'Test',
            'type' => 'product',
            'price' => 1,
        ]);
    }

    public function test_platform_master_without_selected_company_is_redirected_to_the_master_console(): void
    {
        $this->provision('Zeta', 'admin.zeta@example.test');
        $master = User::factory()->create(['is_master_admin' => true, 'is_active' => true]);
        $master->assignRole(Role::firstOrCreate(['name' => 'Super administrateur', 'guard_name' => 'web']));

        $this->actingAs($master)->get(route('dashboard'))
            ->assertRedirect(route('master.companies'));
    }

    /** @return array<string, mixed> */
    private function provision(string $name, string $email): array
    {
        return app(SaasProvisioner::class)->createCompany([
            'name' => $name,
            'administrator_name' => "Administrateur {$name}",
            'administrator_email' => $email,
            'administrator_password' => 'MotDePasse-2026',
        ]);
    }

    private function createProductFor(array $tenant, string $sku): Product
    {
        return app(TenantContext::class)->run(
            $tenant['company'],
            $tenant['establishment'],
            $tenant['point_of_sale'],
            fn () => Product::query()->create([
                'name' => $sku,
                'sku' => $sku,
                'category' => 'Tests SaaS',
                'type' => 'product',
                'price' => 10,
                'cost' => 5,
                'stock_qty' => 3,
                'alert_qty' => 1,
                'is_active' => true,
            ]),
        );
    }

    private function tenantToken(User $user, int $companyId, int $pointOfSaleId): string
    {
        $token = $user->createToken('Test tenant', ApiAbilityCatalog::for($user));
        $token->accessToken->forceFill([
            'company_id' => $companyId,
            'point_of_sale_id' => $pointOfSaleId,
        ])->save();

        return $token->plainTextToken;
    }
}
