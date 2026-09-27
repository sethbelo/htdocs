<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricule' => 'EMP-'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'job_title' => fake()->jobTitle(),
            'employment_status' => EmploymentStatus::Permanent,
            'hired_at' => now()->subYear(),
            'salary_type' => 'monthly',
            'base_salary' => '520',
            'phone' => fake()->phoneNumber(),
        ];
    }
}
