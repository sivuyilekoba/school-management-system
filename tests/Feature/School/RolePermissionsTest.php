<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The permission matrix payload with every managed role.
 *
 * @param  array<string, array<int, string>>  $permissions
 * @return array<string, mixed>
 */
function permissionMatrix(array $permissions = []): array
{
    return [
        'permissions' => [
            'Parent' => $permissions['Parent'] ?? [],
            'Student' => $permissions['Student'] ?? [],
            'Support Staff' => $permissions['Support Staff'] ?? [],
            'Teacher' => $permissions['Teacher'] ?? [],
        ],
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.permissions.edit'))->assertRedirect(route('login'));
});

test('users without the roles permission are forbidden', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.permissions.edit'))
        ->assertForbidden();
});

test('administrators can view the permission matrix', function () {
    $this->actingAs($this->administrator)
        ->get(route('school.permissions.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/permissions/Edit')
            ->has('modules', 7)
            ->has('roles', 4)
            ->has('assignments', 4)
            ->where('roles.0.name', 'Parent')
            ->where('roles.1.name', 'Student')
            ->where('roles.2.name', 'Support Staff')
            ->where('roles.3.name', 'Teacher')
            ->where('modules.0.name', 'School administration'));
});

test('administrators are not listed as an editable role', function () {
    $response = $this->actingAs($this->administrator)
        ->get(route('school.permissions.edit'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('roles', fn ($roles): bool => collect($roles)
            ->pluck('name')
            ->doesntContain('Administrator')));
});

test('administrators can update the permission matrix', function () {
    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), permissionMatrix([
            'Teacher' => ['attendance.record', 'attendance.view'],
            'Student' => ['timetable.view'],
            'Support Staff' => ['people.students.view'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.permissions.edit'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Permissions updated.',
        ]);

    expect(Role::findByName('Teacher')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['attendance.record', 'attendance.view'])
        ->and(Role::findByName('Student')->permissions->pluck('name')->all())
        ->toBe(['timetable.view'])
        ->and(Role::findByName('Parent')->permissions->pluck('name')->all())
        ->toBe([])
        ->and(Role::findByName('Support Staff')->permissions->pluck('name')->all())
        ->toBe(['people.students.view']);
});

test('granted permissions apply to users with the role', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    expect($teacher->can('attendance.record'))->toBeFalse();

    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), permissionMatrix([
            'Teacher' => ['attendance.record'],
        ]))
        ->assertSessionHasNoErrors();

    expect($teacher->fresh()->can('attendance.record'))->toBeTrue();
});

test('permissions that are no longer selected are revoked', function () {
    Role::findByName('Teacher')->givePermissionTo('settings.manage');

    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), permissionMatrix([
            'Teacher' => ['attendance.view'],
        ]))
        ->assertSessionHasNoErrors();

    expect(Role::findByName('Teacher')->hasPermissionTo('attendance.view'))->toBeTrue()
        ->and(Role::findByName('Teacher')->hasPermissionTo('settings.manage'))->toBeFalse();
});

test('the administrator role keeps every permission', function () {
    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), permissionMatrix())
        ->assertSessionHasNoErrors();

    expect(Role::findByName('Administrator')->hasPermissionTo('settings.manage'))->toBeTrue()
        ->and(Role::findByName('Administrator')->hasPermissionTo('roles.manage'))->toBeTrue()
        ->and(Role::findByName('Administrator')->permissions->count())->toBe(Permission::count());
});

test('the administrator role cannot be edited through the matrix', function () {
    $payload = permissionMatrix();
    $payload['permissions']['Administrator'] = [];

    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), $payload)
        ->assertSessionHasErrors('permissions');

    expect(Role::findByName('Administrator')->hasPermissionTo('roles.manage'))->toBeTrue();
});

test('every managed role must be present in the payload', function () {
    $payload = permissionMatrix();
    unset($payload['permissions']['Parent']);

    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), $payload)
        ->assertSessionHasErrors('permissions');
});

test('unknown permissions are rejected', function () {
    $this->actingAs($this->administrator)
        ->put(route('school.permissions.update'), permissionMatrix([
            'Teacher' => ['not.a.permission'],
        ]))
        ->assertSessionHasErrors('permissions.Teacher.0');

    expect(Role::findByName('Teacher')->permissions)->toBeEmpty();
});

test('users without the roles permission cannot update the matrix', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->put(route('school.permissions.update'), permissionMatrix([
            'Teacher' => ['settings.manage'],
        ]))
        ->assertForbidden();

    expect(Role::findByName('Teacher')->permissions)->toBeEmpty();
});
