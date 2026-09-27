<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\SalesReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesModuleTest extends TestCase
{
    use RefreshDatabase;

    private function createManagerWithSale(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        $coffee = Product::create([
            'name' => 'Café maison',
            'sku' => 'CAF-100',
            'category' => 'Boissons',
            'type' => 'product',
            'price' => 10,
            'cost' => 4,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        $meal = Product::create([
            'name' => 'Plat du jour',
            'sku' => 'PLT-100',
            'category' => 'Cuisine',
            'type' => 'product',
            'price' => 20,
            'cost' => 8,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        $sale = Sale::create([
            'user_id' => $manager->id,
            'department' => 'pos',
            'payment_method' => 'cash',
            'total_amount' => 31.80,
            'discount_amount' => 3,
            'tax_amount' => 4.80,
            'paid_amount' => 31.80,
        ]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 10, 'sales_tax_rate' => 16, 'total_price' => 10, 'tax_amount' => 1.60]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $meal->id, 'quantity' => 1, 'unit_price' => 20, 'sales_tax_rate' => 16, 'total_price' => 20, 'tax_amount' => 3.20]);

        return $manager;
    }

    public function test_sales_analysis_reconciles_revenue_tax_cost_and_profit_by_category(): void
    {
        $this->createManagerWithSale();
        $analysis = SalesReportData::build(now()->format('Y-m-d'), now()->format('Y-m-d'));

        $this->assertSame(31.80, $analysis['totals']['revenue']);
        $this->assertSame(27.0, $analysis['totals']['net_before_tax']);
        $this->assertSame(4.80, $analysis['totals']['tax']);
        $this->assertSame(12.0, $analysis['totals']['cost']);
        $this->assertSame(15.0, $analysis['totals']['profit']);
        $this->assertSame(['Cuisine', 'Boissons'], $analysis['categories']->pluck('name')->all());
    }

    public function test_manager_can_open_the_sales_module_and_download_a_real_pdf(): void
    {
        $manager = $this->createManagerWithSale();
        $this->actingAs($manager);
        Setting::set('report_template', 'corporate');
        $date = now()->format('Y-m-d');

        $this->get(route('sales'))
            ->assertOk()
            ->assertSeeText('Ventes & rentabilité')
            ->assertSeeText('Chiffre d’affaires TTC')
            ->assertSeeText('Performance par catégorie')
            ->assertSeeText('Cuisine');

        $response = $this->get(route('sales.pdf', ['start' => $date, 'end' => $date]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Yeboox-Document-Template', 'corporate');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('Rapport-ventes-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_sales_pdf_category_filter_excludes_other_categories(): void
    {
        $this->createManagerWithSale();
        $analysis = SalesReportData::build(now()->format('Y-m-d'), now()->format('Y-m-d'), 'Boissons');

        $this->assertSame(['Boissons'], $analysis['categories']->pluck('name')->all());
        $this->assertSame(10.60, $analysis['totals']['revenue']);
        $this->assertSame(5.0, $analysis['totals']['profit']);
    }
}
