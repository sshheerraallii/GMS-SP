<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.manage',

            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',

            'guards.view',
            'guards.create',
            'guards.edit',
            'guards.delete',

            'events.view',
            'events.create',
            'events.edit',
            'events.delete',

            'shifts.assign',

            'reports.view',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(
            Permission::where('name', '!=', 'users.manage')->get()
        );

        $moderator = Role::firstOrCreate(['name' => 'Moderator']);
        $moderator->givePermissionTo(
            Permission::whereNotIn('name', [
                'users.manage',
                'clients.delete',
                'guards.delete',
                'events.delete',
                'invoices.delete',
            ])->get()
        );

        $shiftAssigner = Role::firstOrCreate(['name' => 'Shift Assigner']);
        $shiftAssigner->givePermissionTo([
            'events.view',
            'shifts.assign',
        ]);
    }
}
