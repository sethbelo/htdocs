<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_screens_render_for_super_admin(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('username', 'directeur.general')->firstOrFail();
        $order = Order::query()->firstOrFail();

        foreach (['dashboard', 'orders.index', 'orders.create', 'cash.checkout', 'cash.sessions', 'pos'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee($order->number);
        $this->actingAs($user)->get(route('print.order-ticket', $order))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
