<?php

namespace App\Policies;

use App\Models\MaintenanceActivityCategory;
use App\Models\User;

class MaintenanceActivityCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Jefe de supervisores', 'Supervisor'], 'web')
            && $user->can('maintenance.activity-category.view');
    }

    public function view(User $user, MaintenanceActivityCategory $category): bool
    {
        return $this->viewAny($user)
            && ($category->active || $this->canManage($user));
    }

    public function create(User $user): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-category.create');
    }

    public function update(User $user, MaintenanceActivityCategory $category): bool
    {
        return $this->canManage($user) && $user->can('maintenance.activity-category.update');
    }

    public function delete(User $user, MaintenanceActivityCategory $category): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, MaintenanceActivityCategory $category): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, MaintenanceActivityCategory $category): bool
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
