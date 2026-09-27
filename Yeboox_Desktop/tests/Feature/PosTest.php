<?php

namespace Tests\Feature;

use App\Livewire\Pos;
use App\Models\AccountingRecord;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        $cashier = User::factory()->create();
        $cashier->assignRole(Role::firstOrCreate(['name' => 'Caissier', 'guard_name' => 'web']));

        return $cashier;
    }

    public function test_pos_formats_product_prices_with_the_configured_base_currency(): void
    {
        $cashier = $this->cashier();
        Setting::set('base_currency', 'CDF');
        Setting::set('product_price_currency', 'CDF');
        Setting::set('secondary_currency', 'USD');
        Setting::set('exchange_rate', 1 / 2800);

        $component = Livewire::actingAs($cashier)->test(Pos::class)->call('setCurrency', 'USD');

        $this->assertSame('12 500 CDF', $component->instance()->displayAmount(12500));
        $this->assertSame('35 000 CDF', $component->instance()->formatCurrencyAmount(35000, 'CDF'));
        $component->assertSet('currency', 'CDF');
    }

    public function test_a_cashier_can_open_the_pos(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->get(route('pos'))
            ->assertOk()
            ->assertSee('images/yeboox-logo.png', false);
    }

    public function test_the_pos_completes_a_cash_sale_and_updates_stock_and_accounting(): void
    {
        $cashier = $this->cashier();
        $product = Product::create([
            'name' => 'Eau minérale',
            'sku' => 'EAU-001',
            'category' => 'Boissons',
            'type' => 'product',
            'price' => 5,
            'cost' => 2,
            'stock_qty' => 10,
            'sales_tax_rate' => 16,
            'is_active' => true,
        ]);
        PaymentMethod::create([
            'name' => 'Cash',
            'code' => 'cash',
            'icon' => 'banknotes',
            'is_active' => true,
            'syscohada_code' => '5711',
        ]);

        $this->actingAs($cashier);

        Livewire::test(Pos::class)
            ->set('openingBalance', 20)
            ->call('openRegister')
            ->call('addProduct', $product->id)
            ->set('fulfillmentType', 'takeaway')
            ->call('startPayment')
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertDispatched('pos-receipt-ready')
            ->assertSet('showReceipt', true)
            ->assertSet('cart', []);

        $sale = Sale::firstOrFail();
        $this->assertSame('takeaway', $sale->fulfillment_type);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertEquals(5.00, $sale->total_amount);
        $this->assertEquals(0.69, $sale->tax_amount);
        $this->assertEquals(9, $product->fresh()->stock_qty);
        $this->assertSame(1, StockMovement::count());
        $this->assertSame(3, AccountingRecord::count());
        $this->assertSame(1, RegisterSession::where('status', 'open')->count());
    }

    public function test_the_pos_adds_tax_only_when_the_catalog_price_excludes_it(): void
    {
        $cashier = $this->cashier();
        RegisterSession::create([
            'user_id' => $cashier->id,
            'opened_at' => now(),
            'opening_balance' => 0,
            'status' => 'open',
        ]);
        $product = Product::create([
            'name' => 'Article HT', 'sku' => 'HT-POS-001', 'category' => 'Tests',
            'type' => 'service', 'price' => 5, 'cost' => 0, 'stock_qty' => 0,
            'sales_tax_rate' => 16, 'tax_included' => false, 'is_active' => true,
        ]);

        $component = Livewire::actingAs($cashier)->test(Pos::class)
            ->call('addProduct', $product->id);

        $this->assertSame(5.0, $component->instance()->subtotal());
        $this->assertSame(0.8, $component->instance()->taxTotal());
        $this->assertSame(5.8, $component->instance()->total());
        $this->assertSame(5.8, $component->instance()->cartLineGrossTotal($component->get('cart')[$product->id]));
    }

    public function test_register_modals_are_never_open_at_the_same_time(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        Livewire::test(Pos::class)
            ->assertSet('showOpenRegister', true)
            ->assertSet('showCloseRegister', false)
            ->set('openingBalance', 10)
            ->set('openingBalanceCdf', 28000)
            ->call('openRegister')
            ->assertHasNoErrors()
            ->assertSet('showOpenRegister', false)
            ->assertSet('showCloseRegister', false)
            ->call('requestCloseRegister')
            ->assertSet('showOpenRegister', false)
            ->assertSet('showCloseRegister', true)
            ->call('closeRegister')
            ->assertHasNoErrors()
            ->assertSet('showOpenRegister', false)
            ->assertSet('showCloseRegister', false)
            ->assertRedirect(route('dashboard'));

        $this->assertSame('closed', RegisterSession::firstOrFail()->status);
    }

    public function test_the_cart_survives_a_component_refresh(): void
    {
        $cashier = $this->cashier();
        $product = Product::create([
            'name' => 'Massage',
            'sku' => 'SRV-001',
            'category' => 'Services',
            'type' => 'service',
            'price' => 25,
            'stock_qty' => 0,
            'is_active' => true,
        ]);
        RegisterSession::create([
            'user_id' => $cashier->id,
            'opened_at' => now(),
            'opening_balance' => 0,
            'status' => 'open',
        ]);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)->call('addProduct', $product->id);

        Livewire::test(Pos::class)
            ->assertSet("cart.{$product->id}.name", 'Massage')
            ->assertSet("cart.{$product->id}.quantity", 1.0);
    }

    public function test_a_barcode_or_qr_payload_adds_the_product_directly_to_the_cart(): void
    {
        $cashier = $this->cashier();
        $product = Product::create([
            'name' => 'Produit scanné', 'sku' => 'SCAN-SKU-01', 'barcode' => '2609220000015',
            'category' => 'Scan', 'type' => 'product', 'price' => 8, 'stock_qty' => 4, 'is_active' => true,
        ]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->set('scanCode', 'YEBOOX:PRODUCT:2609220000015')
            ->call('scanProductCode')
            ->assertHasNoErrors()
            ->assertSet("cart.{$product->id}.quantity", 1.0)
            ->assertSet('scanFeedbackType', 'success')
            ->set('scanCode', 'SCAN-SKU-01')
            ->call('scanProductCode')
            ->assertSet("cart.{$product->id}.quantity", 2.0);
    }

    public function test_scanning_requires_an_open_register(): void
    {
        $cashier = $this->cashier();
        Product::create([
            'name' => 'Produit fermé', 'sku' => 'SCAN-CLOSED', 'barcode' => '12345678',
            'category' => 'Scan', 'type' => 'service', 'price' => 3, 'is_active' => true,
        ]);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->set('scanCode', '12345678')
            ->call('scanProductCode')
            ->assertSet('showOpenRegister', true)
            ->assertSet('scanFeedbackType', 'error')
            ->assertSet('cart', []);
    }

    public function test_each_table_keeps_its_own_cart(): void
    {
        $cashier = $this->cashier();
        $product = Product::create([
            'name' => 'Café',
            'sku' => 'CAF-001',
            'category' => 'Boissons',
            'type' => 'service',
            'price' => 3,
            'is_active' => true,
        ]);
        RegisterSession::create([
            'user_id' => $cashier->id,
            'opened_at' => now(),
            'opening_balance' => 0,
            'status' => 'open',
        ]);
        Setting::set('restaurant_tables', json_encode([
            ['zone' => 'Terrasse', 'tables' => 'A1, A2'],
        ]));

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('selectTable', 'Table A1')
            ->call('addProduct', $product->id)
            ->call('selectTable', 'Table A2')
            ->assertSet('cart', [])
            ->call('selectTable', 'Table A1')
            ->assertSet("cart.{$product->id}.name", 'Café')
            ->assertSet('tableName', 'Table A1');
    }

    public function test_table_selector_marks_free_and_occupied_tables(): void
    {
        $cashier = $this->cashier();
        $product = Product::create(['name' => 'Jus', 'sku' => 'JUS-1', 'category' => 'Boissons', 'type' => 'service', 'price' => 3, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);
        Setting::set('restaurant_tables', json_encode([
            ['zone' => 'Terrasse', 'tables' => 'A1, A2'],
        ]));

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('selectTable', 'Table A1')
            ->call('addProduct', $product->id)
            ->call('openTableSelector')
            ->assertSee('1 libre(s)')
            ->assertSee('1 occupée(s)')
            ->assertSee('Occupée')
            ->assertSee('Libre');
    }

    public function test_register_cannot_close_with_a_pending_table_order(): void
    {
        $cashier = $this->cashier();
        $product = Product::create([
            'name' => 'Thé',
            'sku' => 'THE-001',
            'category' => 'Boissons',
            'type' => 'service',
            'price' => 2,
            'is_active' => true,
        ]);
        RegisterSession::create([
            'user_id' => $cashier->id,
            'opened_at' => now(),
            'opening_balance' => 0,
            'status' => 'open',
        ]);
        Setting::set('restaurant_tables', json_encode([
            ['zone' => 'Salle principale', 'tables' => '1, 2'],
        ]));

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('selectTable', 'Table 1')
            ->call('addProduct', $product->id)
            ->call('requestCloseRegister')
            ->call('closeRegister')
            ->assertHasErrors('closingBalance');

        $this->assertSame('open', RegisterSession::firstOrFail()->status);
    }

    public function test_printing_an_addition_never_clears_the_order(): void
    {
        $cashier = $this->cashier();
        $product = Product::create(['name' => 'Repas', 'sku' => 'REP-1', 'category' => 'Cuisine', 'type' => 'service', 'price' => 10, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('addProduct', $product->id)
            ->call('openAddition')
            ->assertSet('showAddition', true)
            ->assertSet("cart.{$product->id}.quantity", 1.0);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_a_sale_can_be_paid_with_cash_and_mobile_money(): void
    {
        $cashier = $this->cashier();
        $product = Product::create(['name' => 'Formule', 'sku' => 'FOR-1', 'category' => 'Cuisine', 'type' => 'service', 'price' => 10, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('addProduct', $product->id)
            ->set('fulfillmentType', 'takeaway')
            ->set('paymentLines', [
                ['method' => 'cash', 'amount' => 4, 'received' => 10, 'currency' => 'USD', 'provider' => '', 'reference' => ''],
                ['method' => 'mobile_money', 'amount' => 6, 'currency' => 'USD', 'provider' => 'M-Pesa', 'reference' => 'MP-123'],
            ])
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('lastChangeDue', 6.0)
            ->call('requestCloseRegister')
            ->assertSet('closingBalance', 4.0);

        $this->assertSame('multiple', Sale::firstOrFail()->payment_method);
        $this->assertSame(2, SalePayment::count());
        $this->assertDatabaseHas('sale_payments', ['method' => 'mobile_money', 'provider' => 'M-Pesa', 'reference' => 'MP-123']);
        $cashPayment = SalePayment::where('method', 'cash')->firstOrFail();
        $this->assertEquals(10, $cashPayment->metadata['received']);
        $this->assertEquals(6, $cashPayment->metadata['change']);
    }

    public function test_a_sale_forces_every_payment_into_the_single_base_currency(): void
    {
        Setting::set('base_currency', 'USD');
        Setting::set('product_price_currency', 'USD');
        Setting::set('secondary_currency', 'CDF');
        Setting::set('exchange_rate', 2800);
        $cashier = $this->cashier();
        $product = Product::create(['name' => 'Menu mixte', 'sku' => 'MIX-1', 'category' => 'Cuisine', 'type' => 'service', 'price' => 10, 'is_active' => true]);
        RegisterSession::create([
            'user_id' => $cashier->id,
            'opened_at' => now(),
            'opening_balance' => 12,
            'base_currency' => 'USD',
            'secondary_currency' => 'CDF',
            'base_to_secondary_rate' => 2800,
            'status' => 'open',
        ]);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('addProduct', $product->id)
            ->set('fulfillmentType', 'takeaway')
            ->set('paymentLines', [
                ['method' => 'cash', 'amount' => '4,00', 'received' => '5,00', 'currency' => 'USD', 'provider' => '', 'reference' => ''],
                ['method' => 'cash', 'amount' => '6,00', 'received' => '8,00', 'currency' => 'CDF', 'provider' => '', 'reference' => ''],
            ])
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('lastChangeByCurrency.USD', 3.0)
            ->call('requestCloseRegister')
            ->assertSet('closingBalance', 22.0);

        $this->assertDatabaseHas('sale_payments', ['method' => 'cash', 'currency' => 'USD', 'amount' => 4]);
        $this->assertDatabaseHas('sale_payments', ['method' => 'cash', 'currency' => 'USD', 'amount' => 6]);
        $this->assertEquals(10, AccountingRecord::where('sale_id', Sale::firstOrFail()->id)->sum('debit'));
    }

    public function test_customer_points_wallet_is_debited_during_checkout(): void
    {
        $cashier = $this->cashier();
        $customer = Customer::create(['name' => 'Cliente fidèle', 'points' => 300, 'is_active' => true]);
        $product = Product::create(['name' => 'Soin', 'sku' => 'SOI-1', 'category' => 'Spa', 'type' => 'service', 'price' => 5, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);
        Setting::set('monthly_modules', json_encode(['loyalty' => ['enabled' => true, 'expires_at' => now()->addMonth()->toDateString()]]));
        Setting::set('loyalty_mode', 'points');
        Setting::set('loyalty_point_value_usd', 0.02);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->call('addProduct', $product->id)
            ->set('customerId', $customer->id)
            ->set('fulfillmentType', 'takeaway')
            ->set('paymentLines', [
                ['method' => 'points', 'amount' => 5, 'provider' => '', 'reference' => ''],
            ])
            ->call('checkout')
            ->assertHasNoErrors();

        $sale = Sale::firstOrFail();
        $this->assertSame(250, $sale->points_redeemed);
        $this->assertSame(50, $customer->fresh()->points);
        $this->assertDatabaseHas('sale_payments', ['method' => 'points', 'points_redeemed' => 250]);
    }

    public function test_service_loyalty_awards_and_redeems_the_configured_gift(): void
    {
        $cashier = $this->cashier();
        $customer = Customer::create(['name' => 'Client prestations', 'is_active' => true]);
        $reward = Product::create(['name' => 'Massage cadeau', 'sku' => 'GIFT-1', 'category' => 'Spa', 'type' => 'service', 'price' => 10, 'is_active' => true]);
        $paidService = Product::create(['name' => 'Soin payant', 'sku' => 'PAID-1', 'category' => 'Spa', 'type' => 'service', 'price' => 2, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);
        Setting::set('monthly_modules', json_encode(['loyalty' => ['enabled' => true, 'expires_at' => now()->addMonth()->toDateString()]]));
        Setting::set('loyalty_mode', 'services');
        Setting::set('loyalty_required_services', 9);
        Setting::set('loyalty_reward_product_id', $reward->id);

        $this->actingAs($cashier);
        $firstSale = Livewire::test(Pos::class)->set('customerId', $customer->id)->set('fulfillmentType', 'takeaway');
        foreach (range(1, 9) as $unused) {
            $firstSale->call('addProduct', $paidService->id);
        }
        $firstSale->call('startPayment')->call('checkout')->assertHasNoErrors();

        $this->assertSame(0, $customer->fresh()->loyalty_service_count);
        $this->assertSame(1, $customer->fresh()->loyalty_reward_balance);

        $rewardSale = Livewire::test(Pos::class)
            ->set('customerId', $customer->id)
            ->set('fulfillmentType', 'takeaway')
            ->call('addProduct', $paidService->id)
            ->call('applyLoyaltyReward')
            ->assertSet('useLoyaltyReward', true);
        $this->assertSame(2.0, $rewardSale->instance()->total());
        $rewardSale
            ->call('startPayment')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(1, $customer->fresh()->loyalty_service_count);
        $this->assertSame(0, $customer->fresh()->loyalty_reward_balance);
        $this->assertDatabaseHas('sale_items', ['product_id' => $reward->id, 'unit_price' => 0, 'total_price' => 0]);
    }

    public function test_service_loyalty_can_unlock_and_redeem_a_physical_product(): void
    {
        $cashier = $this->cashier();
        $customer = Customer::create(['name' => 'Client cadeau produit', 'is_active' => true]);
        $reward = Product::create(['name' => 'Jus offert', 'sku' => 'GIFT-JUICE', 'category' => 'Boissons', 'type' => 'product', 'price' => 3, 'cost' => 1, 'stock_qty' => 5, 'is_active' => true]);
        $paidService = Product::create(['name' => 'Coupe payante', 'sku' => 'PAID-CUT', 'category' => 'Salon', 'type' => 'service', 'price' => 2, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);
        Setting::set('monthly_modules', json_encode(['loyalty' => ['enabled' => true, 'expires_at' => now()->addMonth()->toDateString()]]));
        Setting::set('loyalty_mode', 'services');
        Setting::set('loyalty_required_services', 2);
        Setting::set('loyalty_reward_product_id', $reward->id);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->set('customerId', $customer->id)
            ->set('fulfillmentType', 'takeaway')
            ->call('addProduct', $paidService->id)
            ->call('addProduct', $paidService->id)
            ->call('startPayment')
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(1, $customer->fresh()->loyalty_reward_balance);
        $this->assertSame(0, $customer->fresh()->loyalty_service_count);

        $rewardSale = Livewire::test(Pos::class)
            ->set('customerId', $customer->id)
            ->set('fulfillmentType', 'takeaway')
            ->call('addProduct', $paidService->id)
            ->call('applyLoyaltyReward')
            ->assertSet('useLoyaltyReward', true);

        $this->assertSame(2.0, $rewardSale->instance()->total());
        $rewardSale->call('startPayment')->call('checkout')->assertHasNoErrors();

        $this->assertSame(0, $customer->fresh()->loyalty_reward_balance);
        $this->assertSame(1, $customer->fresh()->loyalty_service_count);
        $this->assertEquals(4, $reward->fresh()->stock_qty);
        $this->assertDatabaseHas('sale_items', ['product_id' => $reward->id, 'unit_price' => 0, 'total_price' => 0]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $reward->id, 'type' => 'out', 'quantity' => 1]);
    }

    public function test_an_out_of_stock_loyalty_product_cannot_be_applied(): void
    {
        $cashier = $this->cashier();
        $customer = Customer::create(['name' => 'Client cadeau épuisé', 'loyalty_reward_balance' => 1, 'is_active' => true]);
        $reward = Product::create(['name' => 'Produit épuisé', 'sku' => 'GIFT-EMPTY', 'category' => 'Cadeaux', 'type' => 'product', 'price' => 3, 'stock_qty' => 0, 'is_active' => true]);
        RegisterSession::create(['user_id' => $cashier->id, 'opened_at' => now(), 'opening_balance' => 0, 'status' => 'open']);
        Setting::set('monthly_modules', json_encode(['loyalty' => ['enabled' => true, 'expires_at' => now()->addMonth()->toDateString()]]));
        Setting::set('loyalty_mode', 'services');
        Setting::set('loyalty_reward_product_id', $reward->id);

        $this->actingAs($cashier);
        Livewire::test(Pos::class)
            ->set('customerId', $customer->id)
            ->call('applyLoyaltyReward')
            ->assertHasErrors(['cart'])
            ->assertSet('useLoyaltyReward', false)
            ->assertSeeText('Le produit cadeau Produit épuisé est épuisé.');
    }
}
