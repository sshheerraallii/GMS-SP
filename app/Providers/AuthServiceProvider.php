<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Model => Policy mappings (future use)
    ];

    /**
     * Register authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * ---------------------------------------------------------
         * SUPER ADMIN BYPASS
         * ---------------------------------------------------------
         * Super Admin can do everything — no exceptions.
         */
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        /**
         * ---------------------------------------------------------
         * REPORTS
         * ---------------------------------------------------------
         * Admins can view reports (Super Admin already allowed)
         */
        Gate::define('view-reports', function ($user) {
            return $user->hasAnyRole(['Admin', 'Accountant']);
        });

        /**
         * ---------------------------------------------------------
         * INVOICES (FINANCIAL / LEGAL)
         * ---------------------------------------------------------
         * Only Admins & Super Admins
         */
        Gate::define('manage-invoices', function ($user) {
            return $user->hasAnyRole(['Admin', 'Accountant']);
        });

        /**
         * ---------------------------------------------------------
         * CHARGE RATE (FINANCIAL)
         * ---------------------------------------------------------
         * Super Admin (via bypass) + Accountant only.
         * Admin is intentionally excluded.
         */
        Gate::define('view-charge-rate', function ($user) {
            return $user->hasRole('Accountant');
        });

        /**
         * ---------------------------------------------------------
         * GUARD PERSONAL DETAILS
         * ---------------------------------------------------------
         * Everyone except Moderator (Super Admin via bypass;
         * Admin + Accountant here). Moderator gets the restricted
         * Name / Address / Badge / Expiry guard view in Phase 3.
         */
        Gate::define('view-guard-details', function ($user) {
            return $user->hasAnyRole(['Admin', 'Accountant']);
        });

        /**
         * ---------------------------------------------------------
         * GUARD STATEMENT (PAYROLL PDF)
         * ---------------------------------------------------------
         * Super Admin (via bypass) + Accountant only. Admin excluded.
         */
        Gate::define('view-guard-statement', function ($user) {
            return $user->hasRole('Accountant');
        });

        /**
         * ---------------------------------------------------------
         * EXECUTIVE HOMEPAGE
         * ---------------------------------------------------------
         * Super Admin only (passes via the bypass above; all other
         * roles are denied).
         */
        Gate::define('view-executive', function ($user) {
            return false;
        });

        /**
         * ---------------------------------------------------------
         * FUTURE EXTENSIONS (placeholders)
         * ---------------------------------------------------------
         * Examples:
         *
         * Gate::define('manage-payroll', fn ($user) => $user->hasRole('Admin'));
         * Gate::define('view-audit-logs', fn ($user) => $user->hasRole('Admin'));
         */
    }
}
