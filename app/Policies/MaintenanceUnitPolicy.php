<?php

namespace App\Policies;

use App\Models\MaintenanceUnit;
use App\Models\User;

class MaintenanceUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores', 'Supervisor'], 'web')
            && $user->can('maintenance.unit.view');
    }

    public function view(User $user, MaintenanceUnit $unit): bool
    {
        return $this->viewAny($user) && ($unit->active || $this->canManage($user));
    }

    public function create(User $user): bool
    {
        return $this->canManage($user) && $user->can('maintenance.unit.create');
    }

    public function update(User $user, MaintenanceUnit $unit): bool
    {
        return $this->canManage($user) && $user->can('maintenance.unit.update');
    }

    public function delete(User $user, MaintenanceUnit $unit): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MaintenanceUnit $unit): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MaintenanceUnit $unit): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web');
    }
}
