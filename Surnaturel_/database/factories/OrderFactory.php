<?php

namespace Database\Factories;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'CMD-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'site_id' => Site::factory(),
            'ordered_at' => now(),
            'due_at' => now()->addDays(3),
            'priority' => OrderPriority::Normal,
            'status' => OrderStatus::AwaitingPayment,
            'requires_design' => true,
            'subtotal' => 100,
            'total' => 100,
            'deposit_required' => 50,
        ];
    }
}
