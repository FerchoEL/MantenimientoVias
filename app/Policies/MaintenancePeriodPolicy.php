<?php

namespace App\Policies;

use App\Models\MaintenancePeriod;
use App\Models\MaintenanceService;
use App\Models\User;

class MaintenancePeriodPolicy
{
    public function create(User $user, MaintenanceService $service): bool
    {
        return $user->can('maintenance.period.manage') && $user->can('view', $service);
    }

    public function close(User $user, MaintenancePeriod $period, MaintenanceService $service): bool
    {
        return $period->maintenance_service_id === $service->id && $this->create($user, $service);
    }
}
