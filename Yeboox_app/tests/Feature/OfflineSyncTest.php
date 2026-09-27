<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offline_order_is_repriced_and_synchronized_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::whereNotNull('establishment_id')->firstOrFail();
        $product = Product::where('establishment_id', $user->establishment_id)->firstOrFail();
        $paymentMethod = PaymentMethod::where('establishment_id', $user->establishment_id)->firstOrFail();
        Sanctum::actingAs($user);

        $payload = [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 1,
            ]],
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => 1,
            'local_order_number' => 'OFFLINE-TEST-001',
            'order_type' => 'takeaway',
        ];

        $this->postJson('/api/sync/push', $payload)
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        $order = Order::where('offline_reference', 'OFFLINE-TEST-001')->firstOrFail();
        $this->assertEquals($product->effective_price * 2, (float) $order->total);

        $this->postJson('/api/sync/push', $payload)
            ->assertSuccessful()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, Order::where('offline_reference', 'OFFLINE-TEST-001')->count());
    }

    public function test_pull_returns_the_current_product_schema(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::whereNotNull('establishment_id')->firstOrFail();
        Sanctum::actingAs($user);

        $this->getJson('/api/sync/pull')
            ->assertSuccessful()
            ->assertJsonStructure([
                'products' => [['id', 'name', 'price', 'image', 'track_stock', 'current_stock']],
                'categories',
                'payment_methods',
                'synced_at',
            ]);
    }
}
