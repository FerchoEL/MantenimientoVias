<?php

namespace App\Policies;

use App\Models\MaintenanceService;
use App\Models\User;

class MaintenanceServicePolicy
{
    private function isAdministrator(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web');
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user) && $user->can('maintenance.service.view');
    }

    public function view(User $user, MaintenanceService $service): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->can('maintenance.service.manage');
    }

    public function update(User $user, MaintenanceService $service): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MaintenanceService $service): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MaintenanceService $service): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MaintenanceService $service): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
