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

        // Accountant — V3-P1: now carries Admin's FULL permission set
        // (everything except user management) in addition to its own
        // accountant-only gates (view-charge-rate, view-guard-statement),
        // which live in AuthServiceProvider and are unchanged.
        // Deliberately identical to Admin above; the two roles are
        // separated by gates, not by permissions. Admin remains blind
        // to charge rate; Executive stays Super Admin only.
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $accountant->syncPermissions(
            Permission::where('name', '!=', 'users.manage')->get()
        );

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
