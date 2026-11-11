<?php

namespace App\Policies;

use App\Models\MaintenanceActivityConcept;
use App\Models\User;

class MaintenanceActivityConceptPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewCatalog($user);
    }

    public function view(User $user, MaintenanceActivityConcept $concept): bool
    {
        return $this->canViewCatalog($user)
            && ($this->canManage($user) || ($concept->active && $concept->category->active));
    }

    public function create(User $user): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-concept.create');
    }

    public function update(User $user, MaintenanceActivityConcept $concept): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-concept.update');
    }

    public function delete(User $user, MaintenanceActivityConcept $concept): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MaintenanceActivityConcept $concept): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MaintenanceActivityConcept $concept): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canViewCatalog(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores', 'Supervisor'], 'web')
            && $user->can('maintenance.activity-concept.view');
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores'], 'web');
    }
}
