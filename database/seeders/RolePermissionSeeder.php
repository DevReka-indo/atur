<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'management-users.view',
            'management-users.create',
            'management-users.update',
            'management-users.delete',
            'management-users.toggle-status',
            'management-projects.view',
            'management-projects.delete',
            'management-workspaces.view',
            'management-workspaces.delete',
            'project-template-categories.view',
            'project-template-categories.create',
            'project-template-categories.update',
            'project-template-categories.delete',
            'project-templates.view',
            'project-templates.create',
            'project-templates.update',
            'project-templates.delete',
            'roles.view',
            'roles.update',
            'roles.create',
            'permissions.view',
            'permissions.create',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $contributor = Role::firstOrCreate(['name' => 'contributor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        // Seeder hanya menambah akses sistem agar role, permission, dan konfigurasi custom dari web tetap utuh.
        $superAdmin->givePermissionTo($permissionNames);
        $contributor->givePermissionTo([
            'project-template-categories.view',
            'project-template-categories.create',
            'project-template-categories.update',
            'project-templates.view',
            'project-templates.create',
            'project-templates.update',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
