<?php

use App\Http\Controllers\School\RolePermissionsController;
use App\Http\Controllers\School\SchoolSettingsController;
use App\Http\Controllers\School\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('school/settings', [SchoolSettingsController::class, 'edit'])
        ->middleware('can:settings.manage')
        ->name('school.settings.edit');

    Route::patch('school/settings', [SchoolSettingsController::class, 'update'])
        ->middleware('can:settings.manage')
        ->name('school.settings.update');

    Route::get('school/permissions', [RolePermissionsController::class, 'edit'])
        ->middleware('can:roles.manage')
        ->name('school.permissions.edit');

    Route::put('school/permissions', [RolePermissionsController::class, 'update'])
        ->middleware('can:roles.manage')
        ->name('school.permissions.update');

    Route::get('school/users', [UserController::class, 'index'])
        ->middleware('can:users.view')
        ->name('school.users.index');

    Route::get('school/users/create', [UserController::class, 'create'])
        ->middleware('can:users.manage')
        ->name('school.users.create');

    Route::post('school/users', [UserController::class, 'store'])
        ->middleware('can:users.manage')
        ->name('school.users.store');

    Route::get('school/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:users.manage')
        ->name('school.users.edit');

    Route::put('school/users/{user}', [UserController::class, 'update'])
        ->middleware('can:users.manage')
        ->name('school.users.update');
});

require __DIR__.'/settings.php';
