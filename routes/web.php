<?php

use App\Http\Controllers\School\RolePermissionsController;
use App\Http\Controllers\School\SchoolSettingsController;
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
});

require __DIR__.'/settings.php';
