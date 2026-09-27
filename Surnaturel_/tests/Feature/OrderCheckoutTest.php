<?php

namespace Tests\Feature;

use App\Enums\DesignJobStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Client;
use App\Models\JournalEntryLine;
use App\Models\TreasuryAccount;
use App\Services\Cash\OrderCheckoutService;
use App\Services\Cash\PaymentData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_deposit_payment_invoices_posts_accounting_and_routes_to_design(): void
    {
        $order = $this->createOrder(unitPrice: 200);
        $cashier = $this->userWithRole('caissier');
        $session = $this->openSession($cashier);

        $result = app(OrderCheckoutService::class)->checkout($order, [new PaymentData(PaymentMethod::Cash, 100)], $session, $cashier);

        $order->refresh();
        $this->assertSame(OrderStatus::InDesign, $order->status);
        $this->assertEquals(100, (float) $order->amount_paid);
        $this->assertSame(DesignJobStatus::ToDesign, $order->designJob->status);

        $invoice = $result['invoice'];
        $this->assertSame($order->id, $invoice->order_id);
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertEquals(200, (float) $invoice->total);

        $this->assertEquals(150, $session->fresh()->theoreticalCashBalance());
        $this->assertEquals(100, (float) TreasuryAccount::query()->where('code', 'CAI-SIE')->value('balance'));
        $this->assertEquals((float) JournalEntryLine::query()->sum('debit'), (float) JournalEntryLine::query()->sum('credit'));
        $this->assertEquals(100, (float) JournalEntryLine::query()->whereHas('account', fn ($q) => $q->where('code', '411'))->selectRaw('SUM(debit) - SUM(credit) as b')->value('b'));
    }

    public function test_cash_overpayment_returns_change_and_mixed_payment_is_supported(): void
    {
        $order = $this->createOrder(unitPrice: 80, overrides: ['requires_design' => false]);
        $cashier = $this->userWithRole('caissier');
        $session = $this->openSession($cashier);

        $result = app(OrderCheckoutService::class)->checkout($order, PaymentData::collection([
            ['method' => 'mobile_money', 'amount' => 30, 'reference' => 'MM-123'],
            ['method' => 'cash', 'amount' => 60],
        ]), $session, $cashier);

        $this->assertEquals(10, $result['change']);
        $this->assertCount(2, $result['payments']);
        $this->assertEquals(80, (float) $order->fresh()->amount_paid);
        $this->assertSame(OrderStatus::PrintQueue, $order->fresh()->status);
        $this->assertCount(1, $order->printJobs);
        $this->assertSame(InvoiceStatus::Paid, $result['invoice']->status);
    }

    public function test_credit_is_refused_when_client_is_not_allowed(): void
    {
        $order = $this->createOrder();
        $cashier = $this->userWithRole('caissier');
        $session = $this->openSession($cashier);

        $this->expectException(BusinessRuleException::class);
        app(OrderCheckoutService::class)->checkout($order, [new PaymentData(PaymentMethod::Credit, 100)], $session, $cashier);
    }

    public function test_credit_within_limit_releases_order_to_production(): void
    {
        $client = Client::factory()->withCredit(500)->create();
        $order = $this->createOrder(overrides: ['client_id' => $client->id]);
        $cashier = $this->userWithRole('caissier');
        $session = $this->openSession($cashier);

        app(OrderCheckoutService::class)->checkout($order, [new PaymentData(PaymentMethod::Credit, 100)], $session, $cashier);

        $this->assertSame(OrderStatus::InDesign, $order->fresh()->status);
        $this->assertEquals(0, (float) $order->fresh()->amount_paid);
        $this->assertEquals(100, $client->fresh()->outstandingBalance());
    }

    public function test_electronic_payment_requires_a_reference(): void
    {
        $this->expectException(BusinessRuleException::class);
        PaymentData::collection([['method' => 'card', 'amount' => 10]]);
    }
}
