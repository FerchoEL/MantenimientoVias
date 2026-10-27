<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'maintenance.core.view',
            'maintenance.service.view',
            'maintenance.service.manage',
            'maintenance.period.view',
            'maintenance.period.manage',
            'maintenance.activity.view',
            'maintenance.activity.create',
            'maintenance.activity.update',
            'maintenance.finding.view',
            'maintenance.finding.create',
            'maintenance.finding.update',
            'maintenance.inventory.view',
            'maintenance.inventory.move',
            'maintenance.inventory.adjust',
            'maintenance.material.view',
            'maintenance.material.create',
            'maintenance.material.update',
            'maintenance.plant-material.manage',
            'maintenance.report.view',
            'maintenance.report.submit',
            'maintenance.report.review',
            'maintenance.report.request-changes',
            'maintenance.report.select-content',
            'maintenance.report.approve',
            'maintenance.report.download',
            'maintenance.client.view',
            'maintenance.client.manage',
            'maintenance.plant.view',
            'maintenance.plant.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('Administrador', 'web');
        $jefe = Role::findOrCreate('Jefe de supervisores', 'web');
        $supervisor = Role::findOrCreate('Supervisor', 'web');

        $admin->syncPermissions($permissions);

        $jefe->syncPermissions($permissions);

        $supervisor->syncPermissions([
            'maintenance.core.view',
            'maintenance.service.view',
            'maintenance.period.view',
            'maintenance.activity.view',
            'maintenance.activity.create',
            'maintenance.activity.update',
            'maintenance.finding.view',
            'maintenance.finding.create',
            'maintenance.finding.update',
            'maintenance.inventory.view',
            'maintenance.inventory.move',
            'maintenance.material.view',
            'maintenance.report.view',
            'maintenance.report.submit',
            'maintenance.report.download',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
