<?php

namespace Database\Factories;

use App\MaintenancePeriodStatus;
use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenancePeriod> */
class MaintenancePeriodFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'maintenance_service_id' => MaintenanceService::factory(),
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-11',
            'status' => MaintenancePeriodStatus::Open,
            'created_by' => User::factory(),
            'submitted_at' => null,
            'approved_at' => null,
            'closed_at' => null,
        ];
    }
}
