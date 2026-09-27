<?php

namespace Tests\Feature;

use App\Livewire\Inventory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Support\DisplayCurrency;
use App\Support\InventoryStockReportData;
use App\Support\PriceGridCatalog;
use App\Support\ProductSpreadsheet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_unit_and_decimal_product_stock_can_be_created(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCategoryModal')
            ->set('categoryFormName', 'Cosmétiques')
            ->set('categoryCode', 'COSM')
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->call('openUnitModal')
            ->set('unitName', 'Flacon')
            ->set('unitSymbol', 'fl')
            ->set('unitType', 'packaging')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $unit = UnitOfMeasure::where('symbol', 'fl')->firstOrFail();

        $component
            ->call('openCreateProduct')
            ->assertSet('priceEntryCurrency', 'CDF')
            ->set('name', 'Huile premium')
            ->set('categoryName', 'Cosmétiques')
            ->set('unitOfMeasureId', $unit->id)
            ->set('type', 'product')
            ->set('sku', 'COSM-001')
            ->set('price', '35 000,00')
            ->set('salesTaxRate', 16)
            ->set('cost', '14 700,00')
            ->set('internalNotes', 'Réserver au personnel du spa.')
            ->set('stockQty', 2.750)
            ->set('alertQty', 0.500)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = Product::where('sku', 'COSM-001')->firstOrFail();
        $this->assertSame(2.75, (float) $product->stock_qty);
        $this->assertSame($unit->id, $product->unit_of_measure_id);
        $this->assertSame(16.0, (float) $product->sales_tax_rate);
        $this->assertTrue($product->tax_included);
        $this->assertSame(35000.0, (float) $product->price);
        $this->assertSame(14700.0, (float) $product->cost);
        $this->assertSame('Réserver au personnel du spa.', $product->internal_notes);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reason' => 'initial_stock',
            'stock_before' => 0,
            'stock_after' => 2.750,
        ]);
    }

    public function test_product_form_keeps_the_base_currency_and_previews_the_secondary_value(): void
    {
        $user = User::factory()->create();
        Setting::set('base_currency', 'CDF');
        Setting::set('product_price_currency', 'CDF');
        Setting::set('secondary_currency', 'USD');
        Setting::set('exchange_rate', 1 / 2800);

        $component = Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCreateProduct')
            ->set('price', '35 000')
            ->set('cost', '14 000')
            ->call('setPriceEntryCurrency', 'USD')
            ->assertSet('priceEntryCurrency', 'CDF')
            ->assertSet('price', '35 000')
            ->assertSet('cost', '14 000')
            ->call('setPriceEntryCurrency', 'CDF')
            ->assertSet('priceEntryCurrency', 'CDF')
            ->assertSet('price', '35 000')
            ->assertSet('cost', '14 000');

        $this->assertSame('12,50 USD', $component->instance()->priceEntryEquivalent(35000));

        Setting::set('product_price_currency', 'USD');
        Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCreateProduct')
            ->assertSet('priceEntryCurrency', 'CDF');
    }

    public function test_the_configured_tax_mode_is_used_for_new_products_and_can_be_overridden(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::create(['name' => 'Fiscalité', 'code' => 'FISC', 'is_active' => true]);
        $unit = UnitOfMeasure::firstOrCreate(
            ['symbol' => 'u'],
            ['name' => 'Unité', 'type' => 'unit', 'is_active' => true]
        );
        Setting::set('default_tax_included', '0');

        Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCreateProduct')
            ->assertSet('taxIncluded', false)
            ->set('name', 'Produit hors taxe')
            ->set('categoryName', $category->name)
            ->set('sku', 'HT-001')
            ->set('unitOfMeasureId', $unit->id)
            ->set('priceEntryCurrency', 'USD')
            ->set('price', '100')
            ->set('salesTaxRate', 16)
            ->set('cost', '20')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $excluded = Product::where('sku', 'HT-001')->firstOrFail();
        $this->assertFalse($excluded->tax_included);
        $this->assertSame(100.0, $excluded->netUnitPrice());
        $this->assertSame(116.0, $excluded->grossUnitPrice());

        Setting::set('default_tax_included', '1');
        Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCreateProduct')
            ->assertSet('taxIncluded', true)
            ->set('name', 'Produit TTC')
            ->set('categoryName', $category->name)
            ->set('sku', 'TTC-001')
            ->set('unitOfMeasureId', $unit->id)
            ->set('priceEntryCurrency', 'USD')
            ->set('price', '116')
            ->set('salesTaxRate', 16)
            ->set('cost', '20')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $included = Product::where('sku', 'TTC-001')->firstOrFail();
        $this->assertTrue($included->tax_included);
        $this->assertSame(100.0, $included->netUnitPrice());
        $this->assertSame(116.0, $included->grossUnitPrice());
    }

    public function test_combo_product_can_be_created_with_a_generated_sku(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::firstOrFail();
        $firstComponent = Product::create([
            'name' => 'Massage court', 'sku' => 'SRV-COMBO-001', 'category' => $category->name,
            'type' => 'service', 'price' => 30000, 'cost' => 10000, 'is_active' => true,
        ]);
        $secondComponent = Product::create([
            'name' => 'Boisson fraîche', 'sku' => 'SRV-COMBO-002', 'category' => $category->name,
            'type' => 'service', 'price' => 15000, 'cost' => 5000, 'is_active' => true,
        ]);

        $comboComponent = Livewire::actingAs($user)->test(Inventory::class)
            ->call('openCreateProduct')
            ->set('name', 'Pack détente')
            ->set('categoryName', $category->name)
            ->set('type', 'combo')
            ->call('generateSku', true)
            ->set('comboItems.0.product_id', $firstComponent->id)
            ->set('comboItems.0.quantity', 2)
            ->call('addComboItem')
            ->set('comboItems.1.product_id', $secondComponent->id)
            ->set('comboItems.1.quantity', 1)
            ->set('salesTaxRate', 16)
            ->set('internalNotes', 'Combo réservé aux ventes directes.');

        $comboComponent
            ->assertSet('type', 'combo')
            ->assertSet('comboItems.0.product_id', $firstComponent->id)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $combo = Product::where('name', 'Pack détente')->firstOrFail();

        $this->assertSame('combo', $combo->type);
        $this->assertStringStartsWith('CMB-', $combo->sku);
        $this->assertNull($combo->unit_of_measure_id);
        $this->assertSame(0.0, (float) $combo->stock_qty);
        $this->assertSame(16.0, (float) $combo->sales_tax_rate);
        $this->assertSame(75000.0, (float) $combo->price);
        $this->assertSame(25000.0, (float) $combo->cost);
        $this->assertCount(2, $combo->comboItems);
        $this->assertDatabaseHas('product_combo_items', [
            'combo_product_id' => $combo->id,
            'component_product_id' => $firstComponent->id,
            'quantity' => 2,
            'unit_price' => 30000,
        ]);
    }

    public function test_supply_and_physical_inventory_are_audited(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::firstOrFail();
        $unit = UnitOfMeasure::where('symbol', 'kg')->firstOrFail();
        $product = Product::create([
            'name' => 'Produit pesé',
            'sku' => 'WEIGHT-001',
            'category' => $category->name,
            'unit_of_measure_id' => $unit->id,
            'type' => 'product',
            'price' => 10,
            'cost' => 2,
            'stock_qty' => 1.250,
            'alert_qty' => 0.250,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)->test(Inventory::class)
            ->call('openOperation', 'supply', $product->id)
            ->set('adjustQty', 2.500)
            ->set('operationUnitCost', 4)
            ->set('operationReference', 'APP-TEST-001')
            ->call('saveOperation')
            ->assertHasNoErrors();

        $this->assertSame(3.75, (float) $product->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reason' => 'supply',
            'reference' => 'APP-TEST-001',
            'stock_before' => 1.250,
            'stock_after' => 3.750,
        ]);

        $component
            ->call('openPhysicalInventory')
            ->set('inventoryCounts.'.$product->id, 3.100)
            ->call('applyPhysicalInventory')
            ->assertHasNoErrors();

        $this->assertSame(3.1, (float) $product->fresh()->stock_qty);
        $movement = StockMovement::where('product_id', $product->id)->where('reason', 'physical_inventory')->firstOrFail();
        $this->assertSame('out', $movement->type);
        $this->assertSame(0.65, (float) $movement->quantity);
        $this->assertDatabaseCount('accounting_records', 2);
    }

    public function test_manager_can_export_the_filtered_stock_state_as_a_real_pdf(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        $category = ProductCategory::firstOrFail();
        $unit = UnitOfMeasure::firstOrFail();
        $product = Product::create([
            'name' => 'Article PDF',
            'sku' => 'PDF-001',
            'category' => $category->name,
            'unit_of_measure_id' => $unit->id,
            'type' => 'product',
            'price' => 10,
            'cost' => 4,
            'stock_qty' => 7,
            'alert_qty' => 2,
            'is_active' => true,
        ]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'reason' => 'supply',
            'reference' => 'APP-PDF-001',
            'quantity' => 3,
            'unit_cost' => 4,
            'stock_before' => 4,
            'stock_after' => 7,
            'description' => 'Réapprovisionnement contrôlé',
            'user_id' => $manager->id,
        ]);

        Setting::set('base_currency', 'CDF');
        Setting::set('product_price_currency', 'CDF');
        Setting::set('secondary_currency', 'USD');
        Setting::set('exchange_rate', 1 / 2800);
        Setting::set('report_template', 'framed');
        $this->actingAs($manager)->withSession(['ui.currency' => 'CDF']);

        $this->get(route('inventory'))
            ->assertOk()
            ->assertSeeText('Suivi du stock')
            ->assertSeeText('28 CDF');

        $response = $this->get(route('inventory.stock.pdf', ['category' => $category->name]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Yeboox-Document-Template', 'framed');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('Etat-des-stocks-', (string) $response->headers->get('Content-Disposition'));

        $report = InventoryStockReportData::build(null, $category->name, null, now()->toDateString(), now()->toDateString());
        $this->assertSame(1, $report['movementTotals']['count']);
        $this->assertSame(3.0, $report['movementTotals']['entries_quantity']);
        $this->assertSame(3.0, $report['movementTotals']['supplies_quantity']);
        $this->assertSame(12.0, $report['movementTotals']['supplies_value']);
    }

    public function test_inventory_offers_twelve_branded_price_grid_models_grouped_by_category(): void
    {
        $manager = User::factory()->create();
        $category = ProductCategory::firstOrFail();
        Product::create([
            'name' => 'Tarif vedette',
            'sku' => 'TARIF-001',
            'category' => $category->name,
            'type' => 'service',
            'price' => 25,
            'sales_tax_rate' => 16,
            'tax_included' => true,
            'cost' => 5,
            'is_active' => true,
        ]);

        $this->assertCount(12, PriceGridCatalog::templates());
        $this->assertGreaterThanOrEqual(6, collect(PriceGridCatalog::templates())->pluck('layout')->unique()->count());

        Livewire::actingAs($manager)->test(Inventory::class)
            ->call('setSection', 'price_grid')
            ->assertSet('activeSection', 'price_grid')
            ->assertSee('Atelier de grilles tarifaires')
            ->assertSee('Signature')
            ->assertSee('Célébration')
            ->assertSee($category->name)
            ->assertSee('Tarif vedette')
            ->call('selectPriceGridTemplate', 'digital')
            ->assertSet('priceGridTemplate', 'digital');
    }

    public function test_manager_can_export_the_selected_price_grid_as_a_real_pdf(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        $category = ProductCategory::firstOrFail();
        Product::create([
            'name' => 'Service catalogue',
            'sku' => 'CAT-PDF-001',
            'category' => $category->name,
            'type' => 'service',
            'price' => 18,
            'sales_tax_rate' => 16,
            'tax_included' => false,
            'cost' => 4,
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        foreach (array_keys(PriceGridCatalog::templates()) as $template) {
            $response = $this->get(route('inventory.price-grid.pdf', [
                'template' => $template,
                'category' => $category->name,
                'currency' => 'CDF',
                'show_sku' => 1,
                'show_tax' => 1,
            ]));

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeader('X-Yeboox-Price-Grid-Template', $template);
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('Grille-tarifaire-'.$template.'-', (string) $response->headers->get('Content-Disposition'));
        }
    }

    public function test_display_currency_uses_the_single_base_and_secondary_policy(): void
    {
        Setting::set('base_currency', 'EUR');
        Setting::set('product_price_currency', 'EUR');
        Setting::set('secondary_currency', 'XAF');
        Setting::set('exchange_rate', 655.957);

        $this->withSession(['ui.currency' => 'CDF']); // ignoré volontairement
        $this->assertSame('10,00 EUR', DisplayCurrency::format(10));
        $this->assertSame('6 560 XAF', DisplayCurrency::format(10, 'XAF'));
        $this->assertSame('10,00 EUR · 6 560 XAF', DisplayCurrency::formatDual(10));
    }

    #[RunInSeparateProcess]
    public function test_excel_catalog_can_be_previewed_and_imported_transactionally(): void
    {
        $user = User::factory()->create();
        $unit = UnitOfMeasure::where('symbol', 'u')->firstOrFail();
        $existing = Product::create([
            'name' => 'Ancien produit', 'sku' => 'IMP-001', 'category' => 'Ancienne catégorie',
            'unit_of_measure_id' => $unit->id, 'type' => 'product', 'price' => 4,
            'cost' => 2, 'stock_qty' => 7, 'alert_qty' => 1, 'is_active' => true,
        ]);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(ProductSpreadsheet::HEADERS, null, 'A1');
        $spreadsheet->getActiveSheet()->fromArray([
            ['IMP-001', '9876543210128', 'Produit actualisé', 'Bien', 'Import Excel', 'u', 12, 5, 16, 99, 3, 'Oui', 'Stock existant préservé'],
            ['IMP-002', '', 'Nouveau service', 'Service', 'Prestations', '', 25, 8, 0, 0, 0, 'Oui', 'Créé par import'],
            ['', '', 'Produit avec valeurs par défaut', '', 'Import Excel', '', '', '', '', '', '', '', ''],
        ], null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'yeboox-import-');
        app(ProductSpreadsheet::class)->save($spreadsheet, $path);
        $upload = UploadedFile::fake()->createWithContent('produits.xlsx', file_get_contents($path));
        @unlink($path);

        Livewire::actingAs($user)->test(Inventory::class)
            ->call('openImportModal')
            ->set('importFile', $upload)
            ->call('prepareProductImport')
            ->assertHasNoErrors()
            ->assertSet('importStep', 'preview')
            ->assertSet('importSummary.valid', 3)
            ->assertSet('importSummary.create', 2)
            ->assertSet('importSummary.update', 1)
            ->call('importProducts')
            ->assertHasNoErrors()
            ->assertSet('importStep', 'complete')
            ->assertSet('importResult.created', 2)
            ->assertSet('importResult.updated', 1);

        $this->assertSame(7.0, (float) $existing->fresh()->stock_qty);
        $this->assertSame('Produit actualisé', $existing->fresh()->name);
        $this->assertSame('9876543210128', $existing->fresh()->barcode);
        $this->assertSame(16.0, (float) $existing->fresh()->sales_tax_rate);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-002', 'name' => 'Nouveau service']);
        $defaultProduct = Product::where('name', 'Produit avec valeurs par défaut')->firstOrFail();
        $this->assertStringStartsWith('PRD-', $defaultProduct->sku);
        $this->assertSame('product', $defaultProduct->type);
        $this->assertSame(0.0, (float) $defaultProduct->price);
        $this->assertSame(0.0, (float) $defaultProduct->cost);
        $this->assertSame(0.0, (float) $defaultProduct->sales_tax_rate);
        $this->assertSame(0.0, (float) $defaultProduct->stock_qty);
        $this->assertSame(5.0, (float) $defaultProduct->alert_qty);
        $this->assertTrue((bool) $defaultProduct->is_active);
        $this->assertNull($defaultProduct->internal_notes);
        $this->assertDatabaseHas('product_categories', ['name' => 'Import Excel']);
    }

    #[RunInSeparateProcess]
    public function test_excel_template_contains_the_expected_contract_and_instructions(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'yeboox-template-');
        $spreadsheets = app(ProductSpreadsheet::class);
        $spreadsheets->save($spreadsheets->template(), $path);

        $workbook = IOFactory::load($path);
        $this->assertSame(ProductSpreadsheet::HEADERS, $workbook->getSheetByName('Produits')->rangeToArray('A1:M1')[0]);
        $this->assertNotNull($workbook->getSheetByName('Instructions'));
        $this->assertGreaterThan(1000, filesize($path));
        $workbook->disconnectWorksheets();
        @unlink($path);
    }

    #[RunInSeparateProcess]
    public function test_registered_product_list_can_be_exported_as_excel(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('export inventory');
        $category = ProductCategory::firstOrFail();
        Product::create([
            'name' => 'Article exporté', 'sku' => 'EXP-001', 'category' => $category->name,
            'type' => 'service', 'price' => 20, 'cost' => 5, 'stock_qty' => 0,
            'alert_qty' => 0, 'is_active' => true,
        ]);
        $this->travelTo(Carbon::parse('2026-09-03 10:15:30'));

        Livewire::actingAs($user)->test(Inventory::class)
            ->call('exportProducts')
            ->assertFileDownloaded('Catalogue-produits-2026-09-03-101530.xlsx');
    }
}
