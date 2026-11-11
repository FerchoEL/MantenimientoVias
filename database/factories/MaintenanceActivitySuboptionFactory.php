<?php

namespace Database\Factories;

use App\Models\MaintenanceActivityConcept;
use App\Models\MaintenanceActivitySuboption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceActivitySuboption>
 */
class MaintenanceActivitySuboptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_activity_concept_id' => MaintenanceActivityConcept::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
