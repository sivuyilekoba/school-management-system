<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update a user account.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    return [
        'name' => 'Jane Teacher',
        'email' => 'jane.teacher@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'roles' => ['Teacher'],
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.users.index'))->assertRedirect(route('login'));
});

test('users without the users permission cannot list accounts', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.users.index'))
        ->assertForbidden();
});

test('users with the view permission can list accounts', function () {
    Role::findByName('Teacher')->givePermissionTo('users.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/users/Index')
            ->has('users.data', 2)
            ->where('users.total', 2));
});

test('the account list can be searched by name or email', function () {
    User::factory()->create([
        'name' => 'Zara Zebra',
        'email' => 'zara@example.com',
    ]);
    User::factory()->create([
        'name' => 'Yusuf Yak',
        'email' => 'yusuf@example.com',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.users.index', ['q' => 'Zara']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Zara Zebra')
            ->where('search', 'Zara'));

    $this->actingAs($this->administrator)
        ->get(route('school.users.index', ['q' => 'yusuf@']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'yusuf@example.com'));
});

test('administrators can create a user account', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.users.store'), userPayload([
            'email' => 'Jane.Teacher@Example.com',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.users.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'User created.',
        ]);

    $user = User::query()->where('email', 'jane.teacher@example.com')->firstOrFail();

    expect($user->name)->toBe('Jane Teacher')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->hasRole('Teacher'))->toBeTrue();
});

test('creating a user validates the password and unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->administrator)
        ->post(route('school.users.store'), userPayload([
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]))
        ->assertSessionHasErrors(['email', 'password']);

    expect(User::query()->where('email', 'taken@example.com')->count())->toBe(1);
});

test('creating a user requires at least one valid role', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.users.store'), userPayload(['roles' => []]))
        ->assertSessionHasErrors('roles');

    $this->actingAs($this->administrator)
        ->post(route('school.users.store'), userPayload(['roles' => ['Wizard']]))
        ->assertSessionHasErrors('roles.0');
});

test('users without the manage permission cannot create accounts', function () {
    Role::findByName('Teacher')->givePermissionTo('users.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.users.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.users.store'), userPayload())
        ->assertForbidden();

    expect(User::query()->where('email', 'jane.teacher@example.com')->exists())->toBeFalse();
});

test('administrators can update a user account', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $user->assignRole('Student');

    $this->actingAs($this->administrator)
        ->put(route('school.users.update', $user), userPayload([
            'name' => 'New Name',
            'email' => 'new@example.com',
            'roles' => ['Teacher', 'Support Staff'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.users.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'User updated.',
        ]);

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->getRoleNames()->sort()->values()->all())
        ->toBe(['Support Staff', 'Teacher']);
});

test('updating a user keeps their current password', function () {
    $user = User::factory()->create(['email' => 'staff@example.com']);
    $user->assignRole('Teacher');

    $this->actingAs($this->administrator)
        ->put(route('school.users.update', $user), userPayload([
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'ignored-password',
            'password_confirmation' => 'ignored-password',
        ]))
        ->assertSessionHasNoErrors();

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('administrators cannot remove the Administrator role from themselves', function () {
    $this->actingAs($this->administrator)
        ->put(route('school.users.update', $this->administrator), userPayload([
            'name' => $this->administrator->name,
            'email' => $this->administrator->email,
            'roles' => ['Teacher'],
        ]))
        ->assertSessionHasErrors('roles');

    expect($this->administrator->refresh()->hasRole('Administrator'))->toBeTrue();
});

test('only role managers can assign the Administrator role', function () {
    Role::findByName('Teacher')->givePermissionTo('users.manage');

    $manager = User::factory()->create();
    $manager->assignRole('Teacher');

    $this->actingAs($manager)
        ->post(route('school.users.store'), userPayload([
            'email' => 'new.admin@example.com',
            'roles' => ['Administrator'],
        ]))
        ->assertSessionHasErrors('roles');

    expect(User::query()->where('email', 'new.admin@example.com')->exists())->toBeFalse();
});

test('role managers can assign the Administrator role', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.users.store'), userPayload([
            'email' => 'second.admin@example.com',
            'roles' => ['Administrator'],
        ]))
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'second.admin@example.com')->firstOrFail()->hasRole('Administrator'))
        ->toBeTrue();
});

test('administrators can open the edit form for a user', function () {
    $user = User::factory()->create(['name' => 'Edit Me']);
    $user->assignRole('Student');

    $this->actingAs($this->administrator)
        ->get(route('school.users.edit', $user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/users/Edit')
            ->where('user.name', 'Edit Me')
            ->where('user.roles', ['Student'])
            ->has('roles', 5));
});
