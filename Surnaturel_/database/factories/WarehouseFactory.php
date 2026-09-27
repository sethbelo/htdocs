<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'W-'.fake()->unique()->numerify('####'),
            'name' => 'Entrepôt '.fake()->word(),
            'site_id' => Site::factory(),
        ];
    }
}
