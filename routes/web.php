<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecurityGuardController;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\EventController;

// Show the create event form
Route::get('/events/create', [EventController::class, 'create'])->name('events.create');

// Store the event
Route::post('/events', [EventController::class, 'store'])->name('events.store');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/edit', [EventController::class, 'index'])->name('events.edit');
Route::get('/events/show', [EventController::class, 'index'])->name('events.show');
Route::delete('/events/destroy', [EventController::class, 'index'])->name('events.destroy');
Route::get('/test', function () {
    return view('test');
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|

Route::get('/', [SecurityGuardController::class, 'index'])->name('security-guards.index');
Route::get('/create', [SecurityGuardController::class, 'create'])->name('security-guards.create');
Route::post('/security-guards', [SecurityGuardController::class, 'store'])->name('security-guards.store');
Route::get('/security-guards/{id}', [SecurityGuardController::class, 'show'])->name('security-guards.show');
Route::get('/security-guards/{id}/edit', [SecurityGuardController::class, 'edit'])->name('security-guards.edit');
Route::put('/security-guards/{id}/update', [SecurityGuardController::class, 'update'])->name('security-guards.update');
Route::delete('/security-guards/{id}',[SecurityGuardController::class, 'destroy'])->name('security-guards.destroy');
*/
Route::resource('security-guards',SecurityGuardController::class);
use App\Http\Controllers\ClientController;

Route::resource('clients', ClientController::class);



Route::get('/register', [AuthController::class, 'register'])->name('login.register');
Route::post('/register', [AuthController::class, 'registerPost'])->name('register');


Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login.login');
    Route::post('/login', [AuthController::class, 'loginPost'])->name('login');
});
 
Route::group(['middleware' => 'auth'], function () {
    Route::get('/', [SecurityGuardController::class, 'index'])->name('security-guards.index');
    Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');
});