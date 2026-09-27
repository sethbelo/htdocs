<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\JournalEntry;
use App\Models\TreasuryAccount;
use App\Services\Accounting\AccountingSyncService;
use App\Services\Cash\CashSessionReport;
use App\Services\Cash\CashSessionService;
use App\Services\Cash\PaymentData;
use App\Services\Pos\CartLine;
use App\Services\Pos\PosCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class CashClosingTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_closing_report_details_sales_payments_stock_and_accounting(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 10);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01', 20);
        app(PosCheckoutService::class)->checkout([new CartLine($product->id, 2)], [new PaymentData(PaymentMethod::Cash, 20)], $session, $cashier);
        app(PosCheckoutService::class)->checkout([new CartLine($product->id, 1)], [new PaymentData(PaymentMethod::MobileMoney, 10, 'MM-1')], $session, $cashier);

        $report = app(CashSessionReport::class)->build($session->fresh());

        $this->assertSame(2, $report['sales_count']);
        $this->assertEquals(30, $report['sales_total']);
        $this->assertEquals(40, $report['expected_cash']);
        $this->assertEquals(20, $report['by_method']->firstWhere('method', PaymentMethod::Cash)['total']);
        $this->assertEquals(10, $report['by_method']->firstWhere('method', PaymentMethod::MobileMoney)['total']);
        $this->assertSame('ENT-LIM', $report['warehouse']->code);
        $this->assertTrue($report['accounting']['synced']);
        $this->assertSame(2, $report['accounting']['entries']);
    }

    public function test_large_difference_requires_a_supervisor_pin(): void
    {
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01', 50);
        $service = app(CashSessionService::class);

        try {
            $service->close($session, 30, $cashier);
            $this->fail('La clôture avec un écart de 20 aurait dû être bloquée.');
        } catch (BusinessRuleException) {
            $this->assertTrue($session->fresh()->isOpen());
        }

        $manager = $this->userWithRole('responsable-boutique', $this->shop);
        $service->close($session->fresh(), 30, $cashier, 'Billet manquant', ['20' => 1, '10' => 1], $manager, '1234');

        $session->refresh();
        $this->assertEquals(-20, (float) $session->difference);
        $this->assertSame($manager->id, $session->supervisor_id);
        $this->assertNotNull($session->accounting_synced_at);
        $this->assertEquals(['20' => 1, '10' => 1], $session->denominations);
    }

    public function test_closing_resyncs_missing_entries_and_deposits_cash(): void
    {
        $product = $this->stockedProduct('ENT-LIM', 10);
        $cashier = $this->userWithRole('caissier', $this->shop);
        $session = $this->openSession($cashier, 'CS-LIM-01', 0);
        $sale = app(PosCheckoutService::class)->checkout([new CartLine($product->id, 5)], [new PaymentData(PaymentMethod::Cash, 50)], $session, $cashier);

        JournalEntry::query()->where('source_type', 'sale')->where('source_id', $sale->id)->delete();
        $this->assertSame(1, app(AccountingSyncService::class)->status($session)['missing']);

        $bank = TreasuryAccount::query()->where('code', 'BQ-01')->firstOrFail();
        app(CashSessionService::class)->close($session->fresh(), 50, $cashier, depositAmount: 40, depositAccount: $bank);

        $this->assertSame(1, JournalEntry::query()->where('source_type', 'sale')->where('source_id', $sale->id)->count());
        $this->assertTrue(app(AccountingSyncService::class)->status($session->fresh())['synced']);
        $this->assertEquals(40, (float) $bank->fresh()->balance);
        $this->assertEquals(10, (float) TreasuryAccount::query()->where('code', 'CAI-LIM')->value('balance'));
        $this->assertEquals(40, (float) $session->fresh()->deposit_amount);
    }
}
