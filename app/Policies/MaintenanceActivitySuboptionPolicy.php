<?php

namespace App\Policies;

use App\Models\MaintenanceActivitySuboption;
use App\Models\User;

class MaintenanceActivitySuboptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores', 'Supervisor'], 'web')
            && $user->can('maintenance.activity-suboption.view');
    }

    public function view(User $user, MaintenanceActivitySuboption $suboption): bool
    {
        return $this->viewAny($user)
            && ($this->canManage($user) || ($suboption->active && $suboption->concept->active && $suboption->concept->category->active));
    }

    public function create(User $user): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-suboption.create');
    }

    public function update(User $user, MaintenanceActivitySuboption $suboption): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-suboption.update');
    }

    public function delete(User $user, MaintenanceActivitySuboption $suboption): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MaintenanceActivitySuboption $suboption): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MaintenanceActivitySuboption $suboption): bool
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
