<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Services\Sales\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithErp;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use InteractsWithErp, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpErp();
    }

    public function test_order_creation_assigns_sequential_number_and_sends_to_cashier(): void
    {
        $first = $this->createOrder(unitPrice: 25, quantity: 4);
        $second = $this->createOrder();

        $this->assertSame('CMD-'.now()->year.'-000001', $first->number);
        $this->assertSame('CMD-'.now()->year.'-000002', $second->number);
        $this->assertSame(OrderStatus::AwaitingPayment, $first->status);
        $this->assertEquals(100, (float) $first->total);
        $this->assertEquals(50, (float) $first->deposit_required);
        $this->assertSame(
            [OrderStatus::Draft, OrderStatus::AwaitingPayment],
            $first->statusHistories()->reorder('id')->get()->pluck('to_status')->all(),
        );
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = $this->createOrder();

        $this->expectException(BusinessRuleException::class);
        app(OrderWorkflow::class)->transition($order, OrderStatus::Printing);
    }

    public function test_cancellation_requires_a_reason(): void
    {
        $order = $this->createOrder();

        try {
            app(OrderWorkflow::class)->transition($order, OrderStatus::Cancelled);
            $this->fail('Une annulation sans motif doit être refusée.');
        } catch (BusinessRuleException) {
            $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status);
        }

        app(OrderWorkflow::class)->transition($order, OrderStatus::Cancelled, comment: 'Client injoignable');
        $this->assertSame('Client injoignable', $order->fresh()->cancellation_reason);
    }

    public function test_suspended_order_resumes_on_previous_status(): void
    {
        $order = $this->createOrder();
        $workflow = app(OrderWorkflow::class);

        $workflow->transition($order, OrderStatus::Suspended, comment: 'En attente du logo');
        $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status_before_suspension);

        $workflow->resume($order->fresh());
        $this->assertSame(OrderStatus::AwaitingPayment, $order->fresh()->status);
        $this->assertNull($order->fresh()->status_before_suspension);
    }
}
