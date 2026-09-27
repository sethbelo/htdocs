<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Projet '.fake()->words(2, true),
            'prospect_name' => fake()->company(),
            'pipeline_stage_id' => PipelineStage::query()->inRandomOrder()->value('id') ?? PipelineStage::query()->create(['name' => 'Prospect'])->id,
            'estimated_amount' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}
