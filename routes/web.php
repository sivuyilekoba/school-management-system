<?php

use App\Http\Controllers\School\FamilyController;
use App\Http\Controllers\School\RolePermissionsController;
use App\Http\Controllers\School\SchoolSettingsController;
use App\Http\Controllers\School\StaffController;
use App\Http\Controllers\School\StudentController;
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

    Route::get('school/students', [StudentController::class, 'index'])
        ->middleware('can:people.students.view')
        ->name('school.students.index');

    Route::get('school/students/create', [StudentController::class, 'create'])
        ->middleware('can:people.students.manage')
        ->name('school.students.create');

    Route::post('school/students', [StudentController::class, 'store'])
        ->middleware('can:people.students.manage')
        ->name('school.students.store');

    Route::get('school/students/{student}/edit', [StudentController::class, 'edit'])
        ->middleware('can:people.students.manage')
        ->name('school.students.edit');

    Route::put('school/students/{student}', [StudentController::class, 'update'])
        ->middleware('can:people.students.manage')
        ->name('school.students.update');

    Route::get('school/staff', [StaffController::class, 'index'])
        ->middleware('can:people.staff.view')
        ->name('school.staff.index');

    Route::get('school/staff/create', [StaffController::class, 'create'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.create');

    Route::post('school/staff', [StaffController::class, 'store'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.store');

    Route::get('school/staff/{staff}/edit', [StaffController::class, 'edit'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.edit');

    Route::put('school/staff/{staff}', [StaffController::class, 'update'])
        ->middleware('can:people.staff.manage')
        ->name('school.staff.update');

    Route::get('school/families', [FamilyController::class, 'index'])
        ->middleware('can:people.families.view')
        ->name('school.families.index');

    Route::get('school/families/create', [FamilyController::class, 'create'])
        ->middleware('can:people.families.manage')
        ->name('school.families.create');

    Route::post('school/families', [FamilyController::class, 'store'])
        ->middleware('can:people.families.manage')
        ->name('school.families.store');

    Route::get('school/families/{family}/edit', [FamilyController::class, 'edit'])
        ->middleware('can:people.families.manage')
        ->name('school.families.edit');

    Route::put('school/families/{family}', [FamilyController::class, 'update'])
        ->middleware('can:people.families.manage')
        ->name('school.families.update');
});

require __DIR__.'/settings.php';
