<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Enums\StockOperationReason;
use App\Enums\StockTransferStatus;
use App\Exceptions\BusinessRuleException;
use App\Livewire\Inventory\Operations;
use App\Livewire\Pos\Terminal;
use App\Models\Employee;
use App\Models\StockMovement;
use App\Models\StockOperation;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\Inventory\StockOperationService;
use App\Services\Inventory\StockService;
use App\Services\Inventory\StockTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class StockTrackingTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_storekeeper_records_entries_and_exits_with_the_people_involved(): void
    {
        $product = $this->stockedProduct('ENT-CEN', 5);
        $storekeeper = $this->userWithRole('magasinier');
        $employee = Employee::factory()->create(['site_id' => $this->headquarters->id, 'first_name' => 'Paul', 'last_name' => 'Mbala']);
        $this->actingAs($storekeeper);
        $warehouse = Warehouse::query()->where('code', 'ENT-CEN')->firstOrFail();

        Livewire::test(Operations::class)
            ->call('create', 'entry')
            ->set('form.warehouse_id', (string) $warehouse->id)
            ->set('form.counterparty_type', 'other')
            ->set('form.performed_by_name', 'Jacques, chauffeur du fournisseur')
            ->set('form.reference', 'BL-7788')
            ->set('lines.0.product_id', (string) $product->id)
            ->set('lines.0.quantity', '20')
            ->set('lines.0.unit_cost', '3')
            ->call('save')
            ->assertHasNoErrors();

        $entry = StockOperation::query()->where('type', 'entry')->firstOrFail();
        $this->assertSame('Jacques, chauffeur du fournisseur', $entry->performed_by_name);
        $this->assertSame($storekeeper->id, $entry->recorded_by);
        $this->assertEquals(60, (float) $entry->total_value);
        $this->assertEquals(25, $product->stockIn($warehouse));

        $exit = app(StockOperationService::class)->record($warehouse, StockOperationReason::WorkshopConsumption, [
            'counterparty_type' => 'employee', 'employee_id' => $employee->id, 'performed_by_name' => 'Paul Mbala',
        ], [['product_id' => $product->id, 'quantity' => 8]], $storekeeper);

        $movement = StockMovement::query()->where('source_type', 'stock_operation')->where('source_id', $exit->id)->firstOrFail();
        $this->assertSame(StockMovementType::ProductionConsumption, $movement->type);
        $this->assertSame('Paul Mbala', $movement->performerName());
        $this->assertSame($storekeeper->id, $movement->user_id);
        $this->assertEquals(17, $product->stockIn($warehouse));

        $this->expectException(BusinessRuleException::class);
        app(StockOperationService::class)->record($warehouse, StockOperationReason::Loss, ['performed_by_name' => 'X'], [['product_id' => $product->id, 'quantity' => 100]], $storekeeper);
    }

    public function test_transfer_is_tracked_from_request_to_reception_with_discrepancies(): void
    {
        $product = $this->stockedProduct('ENT-CEN', 50);
        [$central, $shop] = [Warehouse::query()->where('code', 'ENT-CEN')->first(), Warehouse::query()->where('code', 'ENT-LIM')->first()];
        $shopManager = $this->userWithRole('responsable-boutique', $this->shop);
        $storekeeper = $this->userWithRole('magasinier');
        $service = app(StockTransferService::class);

        $transfer = $service->request($central, $shop, [['product_id' => $product->id, 'quantity' => 20]], $shopManager);
        $this->assertSame(StockTransferStatus::Requested, $transfer->status);

        try {
            $service->ship($transfer, $shopManager);
            $this->fail('Seul l’entrepôt source peut expédier.');
        } catch (BusinessRuleException) {
        }

        $item = $transfer->items()->first();
        $service->ship($transfer->fresh(), $storekeeper, [$item->id => 18], 'Didier (chauffeur)');
        $this->assertEquals(32, $product->stockIn($central));
        $this->assertEquals(0, $product->stockIn($shop));

        $service->receive($transfer->fresh(), $shopManager, [$item->id => 17], 'Un paquet abîmé');
        $transfer->refresh();

        $this->assertSame(StockTransferStatus::Received, $transfer->status);
        $this->assertEquals(17, $product->stockIn($shop));
        $this->assertTrue($transfer->hasDiscrepancy());
        $this->assertSame('Didier (chauffeur)', $transfer->carrier_name);
        $this->assertSame(2, StockMovement::query()->where('source_type', 'stock_transfer')->where('source_id', $transfer->id)->count());
    }

    public function test_point_of_sale_requests_and_receives_restocking_for_its_warehouse(): void
    {
        $product = $this->stockedProduct('ENT-CEN', 40, ['is_sellable_pos' => true]);
        $shopWarehouse = Warehouse::query()->where('code', 'ENT-LIM')->first();
        app(StockService::class)->move($product, $shopWarehouse, StockMovementType::In, 1);

        $manager = $this->userWithRole('responsable-boutique', $this->shop);
        $manager->sites()->syncWithoutDetaching([$this->headquarters->id]);
        $this->openSession($manager, 'CS-LIM-01');

        Livewire::test(Terminal::class)
            ->call('openStock')
            ->set('restockFromId', Warehouse::query()->where('code', 'ENT-CEN')->value('id'))
            ->set('restockLines.0.product_id', (string) $product->id)
            ->set('restockLines.0.quantity', '10')
            ->call('requestRestock')
            ->assertHasNoErrors();

        $transfer = StockTransfer::query()->firstOrFail();
        $this->assertSame($shopWarehouse->id, $transfer->to_warehouse_id);

        app(StockTransferService::class)->ship($transfer, $this->userWithRole('magasinier'), [], 'Coursier');

        Livewire::test(Terminal::class)->call('receiveTransfer', $transfer->id);
        $this->assertEquals(11, $product->stockIn($shopWarehouse));
    }
}
