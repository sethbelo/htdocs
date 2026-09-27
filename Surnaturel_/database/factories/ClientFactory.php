<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'CLI-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'type' => ClientType::Individual,
            'segment' => 'b2c',
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'city' => 'Kinshasa',
        ];
    }

    public function company(): static
    {
        return $this->state(fn (): array => ['type' => ClientType::Company, 'segment' => 'b2b', 'company_name' => fake()->company()]);
    }

    public function withCredit(float $limit = 1000): static
    {
        return $this->state(fn (): array => ['allow_credit' => true, 'credit_limit' => $limit]);
    }
}
