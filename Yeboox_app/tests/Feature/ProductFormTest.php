<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Livewire\Admin\Products\Form;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_form_manages_service_variants_and_composition(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);
        $category = Category::firstOrFail();
        $unit = Unit::firstOrFail();

        $serviceForm = Livewire::test(Form::class)
            ->set('name', 'Coupe premium')
            ->set('category_id', $category->id)
            ->set('unit_id', $unit->id)
            ->set('product_type', ProductType::Service->value)
            ->set('selling_price', '25000')
            ->call('generateSku')
            ->call('generateBarcode');

        $sku = $serviceForm->get('sku');
        $barcode = $serviceForm->get('barcode');
        $this->assertNotEmpty($sku);
        $this->assertMatchesRegularExpression('/^2\d{12}$/', $barcode);

        $serviceForm->call('save')->assertHasNoErrors();
        $service = Product::where('name', 'Coupe premium')->firstOrFail();
        $this->assertSame(ProductType::Service, $service->type);
        $this->assertFalse($service->track_stock);
        $this->assertSame('0.0000', $service->alert_quantity);

        Livewire::test(Form::class)
            ->set('name', 'T-shirt collection')
            ->set('category_id', $category->id)
            ->set('unit_id', $unit->id)
            ->set('product_type', ProductType::Variable->value)
            ->set('selling_price', '12000')
            ->set('variants', [[
                'id' => null, 'name' => 'Taille L', 'sku' => 'TSHIRT-L', 'barcode' => '',
                'selling_price' => '12000', 'cost_price' => '7000', 'stock_quantity' => '8',
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $variable = Product::where('name', 'T-shirt collection')->firstOrFail();
        $this->assertSame(ProductType::Variable, $variable->type);
        $this->assertTrue(ProductVariant::where('product_id', $variable->id)->where('name', 'Taille L')->exists());

        Livewire::test(Form::class)
            ->set('name', 'Pack beauté')
            ->set('category_id', $category->id)
            ->set('unit_id', $unit->id)
            ->set('product_type', ProductType::Composite->value)
            ->set('selling_price', '30000')
            ->set('components', [[
                'product_id' => $service->id, 'quantity' => '2', 'unit_id' => $unit->id,
            ]])
            ->call('save')
            ->assertHasNoErrors();

        $composite = Product::where('name', 'Pack beauté')->firstOrFail();
        $this->assertSame(ProductType::Composite, $composite->type);
        $this->assertTrue(ProductComponent::where('product_id', $composite->id)
            ->where('component_product_id', $service->id)->where('quantity', 2)->exists());
    }
}
