<?php

namespace Tests\Feature;

use App\Models\AccountingRecord;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_login_issues_a_scoped_expiring_token(): void
    {
        $user = $this->cashier();
        $user->update(['password' => Hash::make('MotDePasse123')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'MotDePasse123',
            'device_name' => 'Samsung test',
        ])->assertOk();

        $response->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonFragment(['sales:write'])
            ->assertJsonMissing(['manage inventory']);
        $this->assertNotEmpty($response->json('data.expires_at'));
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'Android · Samsung test']);
    }

    public function test_role_permission_revocation_immediately_blocks_an_existing_token(): void
    {
        $user = $this->cashier();
        $token = $user->createToken('Android', ['catalog:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/products')->assertOk();

        $user->roles->first()->revokePermissionTo('manage POS');
        $this->withToken($token)->getJson('/api/v1/products')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'API_ACCESS_DENIED');
    }

    public function test_mobile_sale_is_server_calculated_atomic_and_idempotent(): void
    {
        Setting::set('base_currency', 'USD');
        Setting::set('product_price_currency', 'USD');
        Setting::set('secondary_currency', 'CDF');
        Setting::set('exchange_rate', 2800);
        $user = $this->cashier();
        $token = $user->createToken('Android caisse', ['register:write', 'register:read', 'sales:write', 'sales:read'])->plainTextToken;
        PaymentMethod::create(['name' => 'Cash', 'code' => 'cash', 'icon' => 'banknotes', 'is_active' => true, 'syscohada_code' => '5711']);
        $product = Product::create([
            'name' => 'Produit API', 'sku' => 'API-001', 'category' => 'Test', 'type' => 'product',
            'price' => 11.60, 'sales_tax_rate' => 16, 'tax_included' => true,
            'cost' => 4, 'stock_qty' => 5, 'alert_qty' => 1, 'is_active' => true,
        ]);

        $this->withToken($token)->postJson('/api/v1/register/open', [
            'opening_balance_usd' => 0,
            'opening_balance_cdf' => 0,
        ])->assertCreated();

        $payload = [
            'client_reference' => 'android-test-0001',
            'fulfillment_type' => 'takeaway',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['method' => 'cash', 'currency' => 'USD', 'amount' => 11.60, 'received' => 20]],
        ];

        $this->withToken($token)->postJson('/api/v1/sales', $payload)
            ->assertCreated()
            ->assertHeader('X-Idempotent-Replay', 'false')
            ->assertJsonPath('data.total_amount', 11.6)
            ->assertJsonPath('data.tax_amount', 1.6)
            ->assertJsonPath('data.payments.0.metadata.change', 8.4);

        $this->assertSame(4.0, (float) $product->fresh()->stock_qty);
        $this->assertEquals(11.60, AccountingRecord::sum('debit'));
        $this->assertEquals(11.60, AccountingRecord::sum('credit'));

        $this->withToken($token)->postJson('/api/v1/sales', $payload)
            ->assertOk()
            ->assertHeader('X-Idempotent-Replay', 'true');
        $this->assertSame(1, Sale::count());
        $this->assertSame(4.0, (float) $product->fresh()->stock_qty);
    }

    public function test_token_without_required_ability_is_rejected(): void
    {
        $user = $this->cashier();
        $token = $user->createToken('Lecture seule', ['profile:read'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/register/open', [
            'opening_balance_usd' => 0,
            'opening_balance_cdf' => 0,
        ])->assertForbidden();
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'Caissier']));

        return $user;
    }
}
