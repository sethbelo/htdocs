<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Cash\OrderCheckout;
use App\Livewire\Crm\Pipeline;
use App\Livewire\Inventory\Stock;
use App\Livewire\Orders\Create;
use App\Livewire\Pos\Terminal;
use App\Livewire\Settings\Roles;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\PipelineStage;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class ScreenInteractionsTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_sales_rep_creates_an_order_with_specifications(): void
    {
        $this->actingAs($this->userWithRole('commercial'));
        $client = Client::factory()->create();

        Livewire::test(Create::class)
            ->set('client_id', $client->id)
            ->set('items.0.description', 'Cartes de visite')
            ->set('items.0.quantity', '500')
            ->set('items.0.unit_price', '0.1')
            ->set('items.0.tax_rate', '16')
            ->set('items.0.paper', 'Couché mat')
            ->set('items.0.grammage', '350')
            ->set('items.0.print_sides', 'recto_verso')
            ->set('items.0.lamination', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertEquals(58, (float) $order->total);
        $this->assertStringContainsString('350 g/m²', $order->items->first()->specSummary());
        $this->assertStringContainsString('Recto/Verso', $order->items->first()->specSummary());
    }

    public function test_cashier_opens_register_scans_order_and_collects_payment(): void
    {
        $order = $this->createOrder(unitPrice: 40);
        $cashier = $this->userWithRole('caissier');
        $this->actingAs($cashier);
        $register = CashRegister::query()->where('code', 'CS-SIE-01')->firstOrFail();

        Livewire::test(OrderCheckout::class)
            ->set('registerId', $register->id)
            ->set('openingBalance', '25')
            ->set('pin', '1234')
            ->call('openSession')
            ->call('lookup', $order->number)
            ->assertSet('orderId', $order->id)
            ->assertSet('payments.0.amount', '20')
            ->call('payFull')
            ->call('checkout')
            ->assertSet('orderId', null);

        $this->assertEquals(40, (float) $order->fresh()->amount_paid);
        $this->assertSame(OrderStatus::InDesign, $order->fresh()->status);
    }

    public function test_pos_terminal_sells_scanned_products(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 10, ['barcode' => '6001234567890', 'is_sellable_pos' => true]);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $this->openSession($cashier, 'CS-LIM-01');

        Livewire::test(Terminal::class)
            ->call('scan', '6001234567890')
            ->call('scan', '6001234567890')
            ->assertSet('cart.'.$product->id.'.quantity', 2)
            ->call('openPayment')
            ->assertSet('payments.0.amount', '20')
            ->call('checkout')
            ->assertSet('cart', []);

        $this->assertSame(1, Sale::query()->count());
        $this->assertEquals(8, $product->stockIn(Warehouse::query()->where('code', 'ENT-LIM')->value('id')));
    }

    public function test_stock_transfer_between_warehouses(): void
    {
        $product = $this->stockedProduct('ENT-CEN', 30);
        $this->actingAs($this->userWithRole('responsable-entrepot'));
        [$from, $to] = [Warehouse::query()->where('code', 'ENT-CEN')->first(), Warehouse::query()->where('code', 'ENT-LIM')->first()];
        $this->headquarters->users()->first()->sites()->syncWithoutDetaching([$this->shop->id]);

        Livewire::test(Stock::class)
            ->call('openMove', 'transfer')
            ->set('move.product_id', (string) $product->id)
            ->set('move.warehouse_id', (string) $from->id)
            ->set('move.to_warehouse_id', (string) $to->id)
            ->set('move.quantity', '12')
            ->set('move.reason', 'Réassort boutique')
            ->call('saveMove')
            ->assertHasNoErrors();

        $this->assertEquals(18, $product->stockIn($from));
        $this->assertEquals(12, $product->stockIn($to));
    }

    public function test_pipeline_card_moves_between_stages(): void
    {
        $this->actingAs($this->userWithRole('commercial'));
        $stages = PipelineStage::query()->orderBy('position')->get();
        $opportunity = Opportunity::factory()->create(['pipeline_stage_id' => $stages[0]->id, 'site_id' => $this->headquarters->id]);

        Livewire::test(Pipeline::class)->call('move', $opportunity->id, 0, (string) $stages[3]->id);

        $this->assertSame($stages[3]->id, $opportunity->fresh()->pipeline_stage_id);
    }

    public function test_role_permissions_matrix_updates(): void
    {
        $this->actingAs($this->userWithRole('super-admin'));
        $role = Role::findByName('livreur');

        Livewire::test(Roles::class)
            ->call('selectRole', $role->id)
            ->call('toggleModule', 'clients')
            ->call('save');

        $this->assertTrue($role->fresh()->hasPermissionTo('clients.export'));
        $this->assertTrue($role->fresh()->hasPermissionTo('deliveries.update'));
    }
}
