<?php

namespace Database\Factories;

use App\Models\Track;
use App\Models\TrackSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackSection>
 */
class TrackSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'track_id' => Track::factory(),
            'name' => fake()->unique()->bothify('Section-###??'),
            'code' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
