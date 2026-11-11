<?php

namespace Database\Factories;

use App\Models\MaintenanceUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceUnit>
 */
class MaintenanceUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'symbol' => fake()->unique()->lexify('u??'),
            'description' => null,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
