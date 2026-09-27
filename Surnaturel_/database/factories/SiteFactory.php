<?php

namespace Database\Factories;

use App\Enums\SiteType;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'S-'.fake()->unique()->numerify('####'),
            'name' => 'Siège '.fake()->city(),
            'type' => SiteType::Headquarters,
            'city' => fake()->city(),
            'latitude' => fake()->latitude(-4.5, -4.2),
            'longitude' => fake()->longitude(15.2, 15.5),
        ];
    }
}
