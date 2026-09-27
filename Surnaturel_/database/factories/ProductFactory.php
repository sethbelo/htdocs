<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->ean13(),
            'name' => ucfirst(fake()->words(2, true)),
            'type' => ProductType::Merchandise,
            'cost_price' => 2,
            'sale_price' => 5,
            'tax_rate' => 0,
            'min_stock' => 5,
            'is_stockable' => true,
            'is_sellable_pos' => true,
        ];
    }

    public function service(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Service, 'is_stockable' => false, 'is_sellable_pos' => false, 'barcode' => null]);
    }

    public function paper(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::Paper, 'is_sellable_pos' => false]);
    }
}
