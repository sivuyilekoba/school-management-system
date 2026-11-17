<?php

use App\Models\Staff;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update a staff member.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function staffPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Nadia',
        'surname' => 'Haddad',
        'preferred_name' => '',
        'job_title' => 'Mathematics Teacher',
        'email' => 'nadia@example.com',
        'phone' => '+1 555 0110',
        'user_id' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.staff.index'))->assertRedirect(route('login'));
});

test('users without the staff permission cannot list staff', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.staff.index'))
        ->assertForbidden();
});

test('users with the view permission can list staff', function () {
    Staff::factory()->count(2)->create();

    Role::findByName('Teacher')->givePermissionTo('people.staff.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.staff.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/staff/Index')
            ->has('staff.data', 2)
            ->where('staff.total', 2));
});

test('the staff list can be searched by name, job title or email', function () {
    Staff::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
        'job_title' => 'Science Teacher',
        'email' => 'zara@example.com',
    ]);
    Staff::factory()->create([
        'first_name' => 'Yusuf',
        'surname' => 'Yak',
        'job_title' => 'Caretaker',
        'email' => 'yusuf@example.com',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.staff.index', ['q' => 'Zebra']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('staff.data', 1)
            ->where('staff.data.0.name', 'Zara Zebra')
            ->where('search', 'Zebra'));

    $this->actingAs($this->administrator)
        ->get(route('school.staff.index', ['q' => 'Caretaker']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('staff.data', 1)
            ->where('staff.data.0.email', 'yusuf@example.com'));
});

test('administrators can create a staff member with a linked account', function () {
    $account = User::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.staff.store'), staffPayload([
            'user_id' => $account->id,
            'preferred_name' => 'Nad',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.staff.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Staff member created.',
        ]);

    $staff = Staff::query()->firstOrFail();

    expect($staff->first_name)->toBe('Nadia')
        ->and($staff->job_title)->toBe('Mathematics Teacher')
        ->and($staff->user_id)->toBe($account->id)
        ->and($staff->displayName())->toBe('Nad Haddad');
});

test('creating a staff member validates the person details', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.staff.store'), staffPayload([
            'first_name' => '',
            'surname' => '',
            'email' => 'not-an-email',
            'user_id' => 9999,
        ]))
        ->assertSessionHasErrors(['first_name', 'surname', 'email', 'user_id']);

    expect(Staff::query()->count())->toBe(0);
});

test('a user account can only be linked to one staff member', function () {
    $account = User::factory()->create();
    Staff::factory()->create(['user_id' => $account->id]);

    $this->actingAs($this->administrator)
        ->post(route('school.staff.store'), staffPayload([
            'user_id' => $account->id,
        ]))
        ->assertSessionHasErrors('user_id');
});

test('users without the manage permission cannot create or update staff', function () {
    Staff::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('people.staff.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.staff.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.staff.store'), staffPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.staff.update', Staff::query()->firstOrFail()), staffPayload())
        ->assertForbidden();
});

test('administrators can update a staff member', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Old',
        'surname' => 'Name',
    ]);
    $account = User::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.staff.update', $staff), staffPayload([
            'first_name' => 'New',
            'surname' => 'Name',
            'job_title' => 'Head of Science',
            'user_id' => $account->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.staff.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Staff member updated.',
        ]);

    $staff->refresh();

    expect($staff->first_name)->toBe('New')
        ->and($staff->job_title)->toBe('Head of Science')
        ->and($staff->user_id)->toBe($account->id);
});

test('a staff member can be updated while keeping their linked account', function () {
    $account = User::factory()->create();
    $staff = Staff::factory()->create(['user_id' => $account->id]);

    $this->actingAs($this->administrator)
        ->put(route('school.staff.update', $staff), staffPayload([
            'first_name' => 'Renamed',
            'surname' => 'Staff',
            'user_id' => $account->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($staff->refresh()->user_id)->toBe($account->id);
});

test('administrators can open the staff form', function () {
    $staff = Staff::factory()->create([
        'first_name' => 'Edit',
        'surname' => 'Me',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.staff.edit', $staff))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/staff/Edit')
            ->where('staff.first_name', 'Edit')
            ->has('accounts'));
});
