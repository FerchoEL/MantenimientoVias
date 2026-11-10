<?php

namespace Database\Factories;

use App\Models\MaintenanceService;
use App\Models\MaintenanceServiceSupervisor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceServiceSupervisor>
 */
class MaintenanceServiceSupervisorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_service_id' => MaintenanceService::factory(),
            'user_id' => User::factory(),
            'start_date' => '2026-11-05',
            'end_date' => null,
            'active' => true,
        ];
    }

    public function forSupervisor(User $supervisor): static
    {
        return $this->state(fn (): array => ['user_id' => $supervisor->getKey()]);
    }
}
