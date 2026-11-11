<?php

namespace Database\Factories;

use App\Models\MaintenanceActivityCategory;
use App\Models\MaintenanceActivityConcept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceActivityConcept>
 */
class MaintenanceActivityConceptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_activity_category_id' => MaintenanceActivityCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
