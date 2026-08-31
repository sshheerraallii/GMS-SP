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

        // ---------------------------------------------------------
        // Roles — permission sets are AUTHORITATIVE (syncPermissions).
        // Re-running this seeder brings each role's permissions to
        // exactly the set listed here (extras are revoked). This is
        // what lets us strip perms in production, not just add.
        // ---------------------------------------------------------

        // Super Admin — no explicit permissions; the Gate::before
        // bypass in AuthServiceProvider grants everything.
        Role::firstOrCreate(['name' => 'Super Admin']);

        // Admin — everything except user management.
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(
            Permission::where('name', '!=', 'users.manage')->get()
        );

        // Accountant — full invoicing/reports (charge-rate via the
        // view-charge-rate gate), create/edit clients & events, full
        // guard VIEW only. No user management, no deletes, no shift
        // assignment.
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions([
            'clients.view', 'clients.create', 'clients.edit',
            'events.view', 'events.create', 'events.edit',
            'guards.view',
            'reports.view',
            'invoices.view', 'invoices.create', 'invoices.edit',
        ]);

        // Moderator — guards VIEW ONLY + events (no delete) + shift
        // assignment. No guard create/edit (guard personal details are
        // hidden from Moderator, so they must not reach the create/edit
        // forms). No clients module, no reports, no invoicing.
        $moderator = Role::firstOrCreate(['name' => 'Moderator']);
        $moderator->syncPermissions([
            'guards.view',
            'events.view', 'events.create', 'events.edit',
            'shifts.assign',
        ]);

        // Shift Assigner — RETIRED. Intentionally not managed here.
        // Any existing role row and its data are left untouched.
    }
}
