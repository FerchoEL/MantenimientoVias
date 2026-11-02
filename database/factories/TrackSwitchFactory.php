<?php

namespace Database\Factories;

use App\Models\Plant;
use App\Models\TrackSwitch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackSwitch>
 */
class TrackSwitchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plant_id' => Plant::factory(),
            'code' => fake()->unique()->bothify('CAM-#####'),
            'name' => fake()->words(2, true),
            'description' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
