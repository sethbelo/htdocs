<?php

namespace Tests\Feature;

use App\Livewire\FirstRunSetup;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_welcome_slides_precede_the_administrator_form(): void
    {
        $tour = Livewire::test(FirstRunSetup::class)->assertSet('welcomeStep', 0)
            ->assertSee('Votre commerce. Un nouveau départ.')
            ->assertDontSee('Nom complet');
        $tour->call('complete')->assertHasErrors('welcomeStep');
        foreach (['Une caisse qui suit votre rythme.', 'Le bon produit. Le bon prix.', 'Des clients qui aiment revenir.', 'Une vue claire. Une équipe alignée.'] as $title) {
            $tour->call('nextWelcome')->assertSee($title)->assertDontSee('Nom complet');
        }
        $tour->call('previousWelcome')->assertSet('welcomeStep', 3)
            ->call('nextWelcome')->call('nextWelcome')->assertSet('welcomeStep', 5)
            ->assertSee('Nom complet')->assertSee('Première configuration');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_first_run_creates_the_administrator_and_empty_production_database(): void
    {
        Livewire::test(FirstRunSetup::class)
            ->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')
            ->set('adminName', 'Administrateur Yetoo')
            ->set('adminEmail', 'admin@yetoo.test')
            ->set('password', 'Secret2026')
            ->set('password_confirmation', 'Secret2026')
            ->set('baseCurrency', 'CDF')
            ->set('secondaryCurrency', 'USD')
            ->set('exchangeRate', '0.00035714')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $admin = User::where('email', 'admin@yetoo.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('Super administrateur'));
        $this->assertSame('production', Setting::get('database_mode'));
        $this->assertSame('1', Setting::get('setup_completed'));
        $this->assertSame('CDF', Setting::get('base_currency'));
        $this->assertSame('USD', Setting::get('secondary_currency'));
        foreach (['products', 'customers', 'sales', 'appointments', 'stock_movements', 'accounting_records'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_invalid_administrator_data_does_not_create_an_installation(): void
    {
        Livewire::test(FirstRunSetup::class)
            ->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')
            ->set('adminName', 'Administrateur Yetoo')
            ->set('adminEmail', 'adresse-invalide')
            ->set('password', 'court')
            ->set('password_confirmation', 'different')
            ->call('complete')
            ->assertHasErrors(['adminEmail', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_entered_cdf_price_is_preserved_through_inventory_cart_and_sale(): void
    {
        Livewire::test(FirstRunSetup::class)
            ->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')->call('nextWelcome')
            ->set('adminName', 'Administrateur')
            ->set('adminEmail', 'production@example.test')
            ->set('password', 'Secret2026')
            ->set('password_confirmation', 'Secret2026')
            ->call('complete')->assertHasNoErrors();
        Setting::set('base_currency', 'CDF');
        Setting::set('product_price_currency', 'CDF');
        Setting::set('secondary_currency', 'USD');
        Setting::set('exchange_rate', 1 / 2800);
        Livewire::test(\App\Livewire\Inventory::class)
            ->call('openCategoryModal')->set('categoryFormName', 'Articles')
            ->set('categoryCode', 'ART')->call('saveCategory')->assertHasNoErrors();

        foreach ([0, 16] as $tax) {
            Livewire::test(\App\Livewire\Inventory::class)
                ->call('openCreateProduct')
                ->set('name', 'Article à 1000 CDF '.$tax)
                ->set('categoryName', 'Articles')
                ->set('type', 'product')
                ->set('sku', 'CDF-'.$tax)
                ->set('price', '1 000')
                ->call('setPriceEntryCurrency', 'USD')
                ->assertSet('priceEntryCurrency', 'CDF')
                ->set('cost', '500')
                ->set('salesTaxRate', $tax)
                ->set('stockQty', 3)
                ->call('saveProduct')->assertHasNoErrors();
            $product = Product::where('sku', 'CDF-'.$tax)->firstOrFail();
            $this->assertEqualsWithDelta(1000, $product->grossUnitPrice(), 0.0001);

            $pos = Livewire::test(\App\Livewire\Pos::class)
                ->call('setCurrency', 'CDF');
            if ($tax === 0) {
                $pos->set('openingBalance', 0)->call('openRegister')->assertHasNoErrors();
            }
            $pos->call('addProduct', $product->id)->set('fulfillmentType', 'takeaway');
            $this->assertSame('1 000 CDF', $pos->instance()->displayAmount($pos->instance()->total()));
            $pos->call('startPayment')->call('checkout')->assertHasNoErrors();
            $sale = \App\Models\Sale::latest('id')->firstOrFail();
            $this->assertEqualsWithDelta(1000, $sale->total_amount, 0.0001);
            $item = \App\Models\SaleItem::where('sale_id', $sale->id)->firstOrFail();
            $this->assertEqualsWithDelta(1000, ((float) $item->total_price + (float) $item->tax_amount), 0.0001);
            $this->assertSame(2.0, (float) $product->fresh()->stock_qty);
        }
    }
}
