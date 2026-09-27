<?php

namespace Tests\Feature;

use App\Livewire\Admin;
use App\Livewire\Operations;
use App\Models\AccountingRecord;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationsModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_modules_are_available_until_their_expiration(): void
    {
        Setting::set('monthly_modules', json_encode(['purchases' => ['enabled' => true, 'expires_at' => now()->endOfMonth()->toDateString()]]));
        $this->assertTrue(ModuleRegistry::enabled('purchases'));

        Setting::set('monthly_modules', json_encode(['purchases' => ['enabled' => true, 'expires_at' => now()->subDay()->toDateString()]]));
        $this->assertFalse(ModuleRegistry::enabled('purchases'));
    }

    public function test_loyalty_module_requires_manual_activation(): void
    {
        $this->assertFalse(ModuleRegistry::enabled('loyalty'));

        Setting::set('monthly_modules', json_encode(['loyalty' => ['enabled' => true, 'expires_at' => now()->addMonth()->toDateString()]]));

        $this->assertTrue(ModuleRegistry::enabled('loyalty'));
    }

    public function test_administrator_can_configure_service_loyalty(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));
        $gift = Product::create([
            'name' => 'Dixième massage offert', 'sku' => 'GIFT-SPA', 'category' => 'Spa',
            'type' => 'service', 'price' => 20, 'cost' => 0, 'is_active' => true,
        ]);

        Livewire::actingAs($administrator)->test(Admin::class)
            ->set('activeTab', 'modules')
            ->set('monthlyModules.loyalty.enabled', true)
            ->set('monthlyModules.loyalty.expires_at', now()->addMonth()->toDateString())
            ->set('loyaltyMode', 'services')
            ->set('loyaltyRequiredServices', 9)
            ->set('loyaltyRewardProductId', $gift->id)
            ->call('saveMonthlyModules')
            ->assertHasNoErrors();

        $this->assertSame('services', Setting::get('loyalty_mode'));
        $this->assertSame('9', Setting::get('loyalty_required_services'));
        $this->assertSame((string) $gift->id, Setting::get('loyalty_reward_product_id'));
        $this->assertTrue(ModuleRegistry::enabled('loyalty'));
    }

    public function test_administrator_can_choose_a_product_as_the_loyalty_gift(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));
        $gift = Product::create([
            'name' => 'Boisson offerte', 'sku' => 'GIFT-DRINK', 'category' => 'Boissons',
            'type' => 'product', 'price' => 3, 'cost' => 1, 'stock_qty' => 12, 'is_active' => true,
        ]);

        Livewire::actingAs($administrator)->test(Admin::class)
            ->set('activeTab', 'modules')
            ->set('loyaltyMode', 'services')
            ->assertSeeText('Produit · Boisson offerte')
            ->set('monthlyModules.loyalty.enabled', true)
            ->set('monthlyModules.loyalty.expires_at', now()->addMonth()->toDateString())
            ->set('loyaltyRequiredServices', 5)
            ->set('loyaltyRewardProductId', $gift->id)
            ->call('saveMonthlyModules')
            ->assertHasNoErrors();

        $this->assertSame((string) $gift->id, Setting::get('loyalty_reward_product_id'));
    }

    public function test_points_loyalty_accepts_a_french_decimal_and_clears_a_deleted_reward(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));
        Setting::set('loyalty_reward_product_id', 999999);

        Livewire::actingAs($administrator)->test(Admin::class)
            ->set('monthlyModules.loyalty.enabled', true)
            ->set('monthlyModules.loyalty.expires_at', now()->addMonth()->toDateString())
            ->set('loyaltyMode', 'points')
            ->set('loyaltyPointValueUsd', '0,025')
            ->set('loyaltyRewardProductId', 999999)
            ->call('saveMonthlyModules')
            ->assertHasNoErrors();

        $this->assertSame('points', Setting::get('loyalty_mode'));
        $this->assertSame('0.025', Setting::get('loyalty_point_value_usd'));
        $this->assertSame('', Setting::get('loyalty_reward_product_id'));
    }

    public function test_loyalty_validation_errors_are_returned_in_french(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(Role::create(['name' => 'Super administrateur']));

        $component = Livewire::actingAs($administrator)->test(Admin::class)
            ->set('monthlyModules.loyalty.enabled', true)
            ->set('loyaltyMode', 'services')
            ->set('loyaltyRewardProductId', null)
            ->call('saveMonthlyModules')
            ->assertHasErrors(['loyaltyRewardProductId']);

        $this->assertSame(
            'Sélectionnez le produit ou la prestation offert par la carte cadeau.',
            $component->errors()->first('loyaltyRewardProductId')
        );
    }

    public function test_a_purchase_can_be_created_approved_and_posted_to_ohada(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));

        Livewire::actingAs($manager)->test(Operations::class, ['module' => 'purchases'])
            ->set('date', now()->toDateString())
            ->set('party', 'Fournisseur test')
            ->set('description', 'Consommables')
            ->set('subtotal', 100)
            ->set('tax', 16)
            ->set('account', '601')
            ->call('save')
            ->assertHasNoErrors();

        $purchase = Purchase::firstOrFail();
        Livewire::actingAs($manager)->test(Operations::class, ['module' => 'purchases'])
            ->call('approvePurchase', $purchase->id)
            ->assertHasNoErrors();

        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertEquals(116, AccountingRecord::where('reference', $purchase->number)->sum('debit'));
        $this->assertEquals(116, AccountingRecord::where('reference', $purchase->number)->sum('credit'));
    }
}
