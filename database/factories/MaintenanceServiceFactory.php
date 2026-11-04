<?php

namespace Database\Factories;

use App\Models\MaintenanceService;
use App\Models\Plant;
use App\ReportFrequency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceService> */
class MaintenanceServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'plant_id' => Plant::factory(),
            'name' => fake()->bothify('Servicio-###??'),
            'order_number' => null,
            'start_date' => '2026-11-05',
            'end_date' => null,
            'report_frequency' => ReportFrequency::Weekly,
            'notes' => null,
        ];
    }
}
