<?php

namespace Tests\Feature;

use App\Livewire\Kiosk\OrderScreen;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KioskOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_kiosk_creates_a_complete_order_from_server_prices(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::withoutGlobalScopes()->active()->available()->firstOrFail();
        $paymentMethod = PaymentMethod::withoutGlobalScopes()
            ->where('establishment_id', $product->establishment_id)
            ->active()
            ->firstOrFail();
        config()->set('yeboox.kiosk.establishment_id', $product->establishment_id);

        Livewire::test(OrderScreen::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethodId', $paymentMethod->id)
            ->set('customerName', 'Client test')
            ->call('submitOrder')
            ->assertSet('step', 'confirmation');

        $order = Order::withoutGlobalScopes()->where('order_number', 'like', 'KSK-%')->latest()->firstOrFail();
        $this->assertEquals($product->effective_price, (float) $order->total);
        $this->assertTrue($order->is_paid);
        $this->assertNotNull($order->user_id);
        $this->assertCount(1, $order->items);
    }
}
