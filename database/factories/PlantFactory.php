<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plant> */
class PlantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->unique()->bothify('Planta #### ????'),
            'short_name' => null,
            'active' => true,
            'observations' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['active' => false]);
    }
}
