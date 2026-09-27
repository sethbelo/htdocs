<?php

namespace Tests\Feature;

use App\Livewire\Admin;
use App\Livewire\Reports;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagementPagesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_brand_uses_the_company_initial_with_theme_aware_colors(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view dashboard');
        Setting::set('company_name', 'Kivu Market');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Accueil de Kivu Market"', false)
            ->assertSee('>K</span>', false)
            ->assertSee('dark:bg-violet-300', false);
    }

    public function test_the_redesigned_management_pages_render_for_a_manager(): void
    {
        Role::create(['name' => 'Caissier']);
        Role::create(['name' => 'Super administrateur']);
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        $this->actingAs($manager);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Vue d’ensemble')
            ->assertSeeText('Accès rapide')
            ->assertSeeText('Encaisser une commande')
            ->assertSeeText('Journal OHADA')
            ->assertSee(route('pos'), false);
        $this->get(route('appointments'))->assertOk()->assertSee('Planning centralisé');
        $this->get(route('customers'))->assertOk()->assertSee('Relation client');
        $this->get(route('reports'))->assertOk()->assertSeeText('Pilotage & performance');
    }

    public function test_sales_reports_use_the_configured_a4_model_and_group_products(): void
    {
        Role::create(['name' => 'Caissier']);
        Role::create(['name' => 'Super administrateur']);
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        $this->actingAs($manager);

        Setting::set('report_template', 'corporate');

        $product = Product::create([
            'name' => 'Café premium',
            'sku' => 'CAFE-001',
            'category' => 'Boissons chaudes',
            'type' => 'product',
            'price' => 5,
            'cost' => 2,
            'stock_qty' => 20,
            'alert_qty' => 4,
        ]);
        $sale = Sale::create([
            'user_id' => $manager->id,
            'department' => 'bar',
            'payment_method' => 'cash',
            'total_amount' => 15,
            'paid_amount' => 15,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 5,
            'total_price' => 15,
        ]);

        $response = $this->get(route('reports'))
            ->assertOk()
            ->assertSee('data-report-template="corporate"', false)
            ->assertSeeText('A4 · Corporate')
            ->assertSeeText('Produits les plus commandés')
            ->assertSeeText('Café premium')
            ->assertSeeText('Boissons chaudes');

        $html = $response->getContent();
        $this->assertStringContainsString('break-inside: auto !important', $html);
        $this->assertStringContainsString('--report-accent: #0369a1', $html);
        $this->assertLessThan(strpos($html, 'yb-print-flow space-y-6'), strpos($html, 'yb-print-header yb-a4-document'));
        $this->assertLessThan(strpos($html, 'yb-print-footer hidden'), strpos($html, 'yb-print-flow space-y-6'));
    }

    public function test_stock_and_register_administrative_reports_render_cleanly(): void
    {
        $manager = User::factory()->create();

        Livewire::actingAs($manager)
            ->test(Reports::class)
            ->set('reportType', 'stock')
            ->assertSeeText('Mouvements de stock')
            ->assertSeeText('Alertes de rupture')
            ->assertSeeText('Document administratif de gestion')
            ->set('reportType', 'sessions')
            ->assertSeeText('Sessions de caisse')
            ->assertSeeText('Registre des ouvertures, clôtures et encaissements par agent.');
    }

    public function test_payment_methods_configuration_table_renders_with_accounting_context(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'Super administrateur']));
        PaymentMethod::create([
            'name' => 'Banque',
            'code' => 'bank',
            'is_active' => true,
            'syscohada_code' => '521',
        ]);

        Livewire::actingAs($admin)
            ->test(Admin::class)
            ->set('activeTab', 'payment_methods')
            ->assertSeeText('Moyens de paiement')
            ->assertSeeText('Moyen d’encaissement')
            ->assertSeeText('Compte SYSCOHADA')
            ->assertSeeText('Banque')
            ->assertSeeText('521');
    }
}
