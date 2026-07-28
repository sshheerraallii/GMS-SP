<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SecurityGuardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\GuardInvoiceController;

use App\Http\Controllers\Reports\ReportsHomeController;
use App\Http\Controllers\Reports\EventReportController;
use App\Http\Controllers\Reports\GuardReportController;
use App\Http\Controllers\ChaseupController;

/*
|--------------------------------------------------------------------------
| Public / Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login.login');
    Route::post('/login', [AuthController::class, 'loginPost'])->name('login');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home / Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/', [SecurityGuardController::class, 'index'])->name('dashboard');
    Route::get('/home', fn () => view('home'))->name('home');

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Register Admin (keep restricted as you wish)
    |--------------------------------------------------------------------------
    */
    Route::get('/register', [AuthController::class, 'register'])->name('login.register');
    Route::post('/register', [AuthController::class, 'store'])->name('login.register.store');

    /*
    |--------------------------------------------------------------------------
    | Invoices (Client Invoices) — Admin / Super Admin only via Gate
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:manage-invoices')
        ->prefix('invoices')
        ->name('invoices.')
        ->group(function () {

            Route::get('/', [InvoiceController::class, 'index'])->name('index');

            // IMPORTANT: static paths BEFORE "/{invoice}"

            Route::get('/{invoice}', [InvoiceController::class, 'show'])
                ->whereNumber('invoice')
                ->name('show');

            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])
                ->whereNumber('invoice')
                ->name('edit');

            Route::put('/{invoice}', [InvoiceController::class, 'update'])
                ->whereNumber('invoice')
                ->name('update');

            Route::post('/{invoice}/issue', [InvoiceController::class, 'issue'])
                ->whereNumber('invoice')
                ->name('issue');

            Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])
                ->whereNumber('invoice')
                ->name('pdf');

            Route::get('/{invoice}/download', [InvoiceController::class, 'download'])
                ->whereNumber('invoice')
                ->name('download');
        });

    // Generate draft client invoice for an event (kept outside invoices prefix)
    Route::post('/events/{event}/invoices/generate-draft', [InvoiceController::class, 'generateDraftForEvent'])
        ->whereNumber('event')
        ->name('invoices.generateDraftForEvent')
        ->middleware('can:manage-invoices');

    /*
    |--------------------------------------------------------------------------
    | Guard Invoices (Supplier/Payables) — Admin / Super Admin only via Gate
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:manage-invoices')->group(function () {

        Route::prefix('guard-invoices')->name('guard-invoices.')->group(function () {

            Route::get('/', [GuardInvoiceController::class, 'index'])->name('index');

            Route::get('/{guardInvoice}', [GuardInvoiceController::class, 'show'])
                ->whereNumber('guardInvoice')
                ->name('show');

            Route::get('/{guardInvoice}/edit', [GuardInvoiceController::class, 'edit'])
                ->whereNumber('guardInvoice')
                ->name('edit');

            Route::put('/{guardInvoice}', [GuardInvoiceController::class, 'update'])
                ->whereNumber('guardInvoice')
                ->name('update');

            Route::post('/{guardInvoice}/issue', [GuardInvoiceController::class, 'issue'])
                ->whereNumber('guardInvoice')
                ->name('issue');

            Route::post('/{guardInvoice}/mark-paid', [GuardInvoiceController::class, 'markPaid'])
                ->whereNumber('guardInvoice')
                ->name('mark-paid');

            Route::get('/{guardInvoice}/pdf', [GuardInvoiceController::class, 'pdf'])
                ->whereNumber('guardInvoice')
                ->name('pdf');

            Route::get('/{guardInvoice}/download', [GuardInvoiceController::class, 'download'])
                ->whereNumber('guardInvoice')
                ->name('download');
        });

        // From Event page: bulk generate guard invoices
        Route::post('/events/{event}/guard-invoices/generate', [GuardInvoiceController::class, 'generateForEvent'])
            ->whereNumber('event')
            ->name('events.guard-invoices.generate');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports (Admin / Super Admin only via Gate)
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:view-reports')
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {

            Route::get('/', [ReportsHomeController::class, 'index'])->name('home');

            // Event Reports
            Route::get('/events', [EventReportController::class, 'index'])->name('events.index');

            Route::get('/events/{event}', [EventReportController::class, 'show'])
                ->whereNumber('event')
                ->name('events.show');

            Route::get('/events/{event}/detailed', [EventReportController::class, 'detailed'])
                ->whereNumber('event')
                ->name('events.detailed');

            // Guard Reports
            Route::get('/guards', [GuardReportController::class, 'index'])->name('guards.index');

            Route::get('/guards/{guard}/events/{event}', [GuardReportController::class, 'show'])
                ->whereNumber('guard')
                ->whereNumber('event')
                ->name('guards.show');
        });

    /*
    |--------------------------------------------------------------------------
    | Security Guards
    |--------------------------------------------------------------------------
    */
    Route::prefix('security-guards')->name('security-guards.')->group(function () {

        Route::middleware('permission:guards.view')->group(function () {
            Route::get('/', [SecurityGuardController::class, 'index'])->name('index');

            Route::get('/{security_guard}', [SecurityGuardController::class, 'show'])
                ->whereNumber('security_guard')
                ->name('show');

            Route::get('/{security_guard}/pdf', [SecurityGuardController::class, 'pdf'])
                ->whereNumber('security_guard')
                ->name('pdf');
        });
        
        Route::get('/{security_guard}/custom-pdf', [SecurityGuardController::class, 'customPdf'])
    ->whereNumber('security_guard')
    ->name('customPdf');
        

        Route::middleware('permission:guards.create')->group(function () {
            Route::get('/create', [SecurityGuardController::class, 'create'])->name('create');
            Route::post('/', [SecurityGuardController::class, 'store'])->name('store');
        });

        Route::middleware('permission:guards.edit')->group(function () {
            Route::get('/{security_guard}/edit', [SecurityGuardController::class, 'edit'])
                ->whereNumber('security_guard')
                ->name('edit');

            Route::match(['put', 'patch'], '/{security_guard}', [SecurityGuardController::class, 'update'])
                ->whereNumber('security_guard')
                ->name('update');
        });

        Route::middleware('permission:guards.delete')->group(function () {
            Route::delete('/{security_guard}', [SecurityGuardController::class, 'destroy'])
                ->whereNumber('security_guard')
                ->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('clients')->name('clients.')->group(function () {

        Route::middleware('permission:clients.view')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');

            Route::get('/{client}', [ClientController::class, 'show'])
                ->whereNumber('client')
                ->name('show');
        });

        Route::middleware('permission:clients.create')->group(function () {
            Route::get('/create', [ClientController::class, 'create'])->name('create');
            Route::post('/', [ClientController::class, 'store'])->name('store');
        });

        Route::middleware('permission:clients.edit')->group(function () {
            Route::get('/{client}/edit', [ClientController::class, 'edit'])
                ->whereNumber('client')
                ->name('edit');

            Route::match(['put', 'patch'], '/{client}', [ClientController::class, 'update'])
                ->whereNumber('client')
                ->name('update');
        });

        Route::middleware('permission:clients.delete')->group(function () {
            Route::delete('/{client}', [ClientController::class, 'destroy'])
                ->whereNumber('client')
                ->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    Route::prefix('events')->name('events.')->group(function () {

        Route::middleware('permission:events.view')->group(function () {

            Route::get('/', [EventController::class, 'index'])->name('index');

            // Excel Staff Sheet (MUST be inside /events prefix, so path is "/events/{event}/staff-sheet")
            Route::get('/{event}/staff-sheet', [EventController::class, 'exportStaffSheet'])
                ->whereNumber('event')
                ->name('staffSheet');
                
                Route::get('/{event}/staff-payment-sheet', [EventController::class, 'exportStaffPaymentSheet'])
    ->whereNumber('event')
    ->name('staffPaymentSheet');
    
    
    Route::get('/{event}/time-sheet', [EventController::class, 'exportTimeSheet'])
    ->whereNumber('event')
    ->name('timeSheet');
                

            Route::get('/{event}', [EventController::class, 'show'])
                ->whereNumber('event')
                ->name('show');
        });

        Route::middleware('permission:events.create')->group(function () {
            Route::get('/create', [EventController::class, 'create'])->name('create');
            Route::post('/import-preview', [EventController::class, 'importPreview'])->name('importPreview');
            Route::post('/', [EventController::class, 'store'])->name('store');
        });

        Route::middleware('permission:events.edit')->group(function () {
            Route::get('/{event}/edit', [EventController::class, 'edit'])
                ->whereNumber('event')
                ->name('edit');

            Route::match(['put', 'patch'], '/{event}', [EventController::class, 'update'])
                ->whereNumber('event')
                ->name('update');
                
                Route::post('/{event}/additional-days-preview', [EventController::class, 'additionalDaysPreview'])
    ->whereNumber('event')
    ->name('additionalDaysPreview');

Route::post('/{event}/additional-days-import', [EventController::class, 'importAdditionalDays'])
    ->whereNumber('event')
    ->name('importAdditionalDays');
                
                
        });

        Route::middleware('permission:events.delete')->group(function () {
            Route::delete('/{event}', [EventController::class, 'destroy'])
                ->whereNumber('event')
                ->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Events – Assign Guards & Schedules
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:shifts.assign')->group(function () {
            Route::get('/{event}/guards', [EventController::class, 'guards'])
                ->whereNumber('event')
                ->name('guards');

            Route::post('/{event}/update-guards', [EventController::class, 'updateGuards'])
                ->whereNumber('event')
                ->name('updateGuards');
                
                Route::post('/{event}/guards/slot/save', [EventController::class, 'saveGuardSlot'])
    ->whereNumber('event')
    ->name('saveGuardSlot');
                
        });
    });

    /*
    |--------------------------------------------------------------------------
    | ChaseUp
    |--------------------------------------------------------------------------
    */
    Route::prefix('chaseup')->name('chaseup.')->group(function () {
        Route::get('/', [ChaseupController::class, 'index'])->name('index');
        Route::post('/shift/{eventShift}/field', [ChaseupController::class, 'updateField'])
            ->whereNumber('eventShift')
            ->name('updateField');

        Route::get('/shift/{eventShift}/latest-actor', [ChaseupController::class, 'latestActor'])
            ->whereNumber('eventShift')
            ->name('latestActor');

        Route::get('/shift/{eventShift}/history', [ChaseupController::class, 'history'])
            ->whereNumber('eventShift')
            ->name('history');
    });



    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Optional test route
    |--------------------------------------------------------------------------
    */
    Route::get('/test', fn () => view('test'))->name('test');
});
