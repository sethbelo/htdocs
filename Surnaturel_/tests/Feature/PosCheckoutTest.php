<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\CashMovement;
use App\Models\Client;
use App\Models\Journal;
use App\Models\JournalEntryLine;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Cash\PaymentData;
use App\Services\Pos\CartLine;
use App\Services\Pos\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_validated_sale_runs_every_downstream_step(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 10);
        $client = Client::factory()->create();
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01', 20);

        $sale = app(PosCheckoutService::class)->checkout(
            [new CartLine($product->id, 3)],
            [new PaymentData(PaymentMethod::Cash, 50)],
            $session, $cashier, $client,
        );

        $this->assertEquals(30, (float) $sale->total);
        $this->assertEquals(20, (float) $sale->change_due);
        $this->assertCount(1, $sale->payments);
        $this->assertEquals(30, (float) $sale->payments->first()->amount);
        $this->assertEquals(7, $product->stockIn($sale->warehouse_id));
        $this->assertSame(StockMovementType::Sale, StockMovement::query()->where('source_type', 'sale')->where('source_id', $sale->id)->firstOrFail()->type);
        $this->assertEquals(30, (float) CashMovement::query()->where('source_type', 'sale')->where('source_id', $sale->id)->sum('amount'));
        $this->assertEquals(50, $session->fresh()->theoreticalCashBalance());
        $this->assertEquals((float) JournalEntryLine::query()->sum('debit'), (float) JournalEntryLine::query()->sum('credit'));
        $this->assertGreaterThan(0, $sale->loyalty_points_earned);
        $this->assertSame($sale->loyalty_points_earned, $client->fresh()->loyaltyAccount->points_balance);
        $this->assertNotNull($sale->invoice);
    }

    public function test_insufficient_stock_rolls_back_the_whole_sale(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 1);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01');

        try {
            app(PosCheckoutService::class)->checkout([new CartLine($product->id, 5)], [new PaymentData(PaymentMethod::Cash, 100)], $session, $cashier);
            $this->fail('La vente aurait dû être refusée.');
        } catch (BusinessRuleException $exception) {
            $this->assertStringContainsString('Stock insuffisant', $exception->getMessage());
        }

        $this->assertSame(0, Sale::query()->count());
        $this->assertEquals(1, $product->stockIn(Warehouse::query()->where('code', 'ENT-LIM')->value('id')));
        $this->assertEquals(0, (float) JournalEntryLine::query()->whereHas('entry', fn ($q) => $q->where('journal_id', Journal::byCode('VEN')->id))->sum('credit'));
    }

    public function test_insufficient_payment_is_rejected(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 5);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01');

        $this->expectException(BusinessRuleException::class);
        app(PosCheckoutService::class)->checkout([new CartLine($product->id, 2)], [new PaymentData(PaymentMethod::Cash, 5)], $session, $cashier);
    }

    public function test_promotion_code_and_discount_permission_are_enforced(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 5);
        Promotion::query()->create(['name' => 'Rentrée', 'code' => 'RENTREE', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01');

        $sale = app(PosCheckoutService::class)->checkout([new CartLine($product->id, 2)], [new PaymentData(PaymentMethod::Cash, 18)], $session, $cashier, promotionCode: 'RENTREE');
        $this->assertEquals(18, (float) $sale->total);

        $this->expectException(BusinessRuleException::class);
        app(PosCheckoutService::class)->checkout([new CartLine($product->id, 1, discount: 2)], [new PaymentData(PaymentMethod::Cash, 10)], $session, $cashier);
    }
}
