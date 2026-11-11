<?php

namespace Database\Factories;

use App\Models\MaintenanceActivityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceActivityCategory>
 */
class MaintenanceActivityCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
