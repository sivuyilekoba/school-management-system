<?php

use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update a student.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Amara',
        'surname' => 'Okafor',
        'preferred_name' => '',
        'gender' => 'Female',
        'date_of_birth' => '2012-03-04',
        'email' => 'amara@example.com',
        'family_id' => null,
        'user_id' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.students.index'))->assertRedirect(route('login'));
});

test('users without the students permission cannot list students', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.students.index'))
        ->assertForbidden();
});

test('users with the view permission can list students', function () {
    Student::factory()->count(2)->create();

    Role::findByName('Teacher')->givePermissionTo('people.students.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/students/Index')
            ->has('students.data', 2)
            ->where('students.total', 2));
});

test('the student list can be searched by name or email', function () {
    Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
        'email' => 'zara@example.com',
    ]);
    Student::factory()->create([
        'first_name' => 'Yusuf',
        'surname' => 'Yak',
        'email' => 'yusuf@example.com',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.students.index', ['q' => 'Zebra']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.name', 'Zara Zebra')
            ->where('search', 'Zebra'));

    $this->actingAs($this->administrator)
        ->get(route('school.students.index', ['q' => 'yusuf@']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.email', 'yusuf@example.com'));
});

test('administrators can create a student with a family and account', function () {
    $family = Family::factory()->create();
    $account = User::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.students.store'), studentPayload([
            'family_id' => $family->id,
            'user_id' => $account->id,
            'preferred_name' => 'Amy',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.students.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Student created.',
        ]);

    $student = Student::query()->firstOrFail();

    expect($student->first_name)->toBe('Amara')
        ->and($student->preferred_name)->toBe('Amy')
        ->and($student->family_id)->toBe($family->id)
        ->and($student->user_id)->toBe($account->id)
        ->and($student->date_of_birth?->toDateString())->toBe('2012-03-04')
        ->and($student->displayName())->toBe('Amy Okafor');
});

test('creating a student validates the person details and links', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.students.store'), studentPayload([
            'first_name' => '',
            'surname' => '',
            'gender' => 'Wizard',
            'date_of_birth' => now()->addDay()->toDateString(),
            'email' => 'not-an-email',
            'family_id' => 9999,
            'user_id' => 9999,
        ]))
        ->assertSessionHasErrors([
            'first_name',
            'surname',
            'gender',
            'date_of_birth',
            'email',
            'family_id',
            'user_id',
        ]);

    expect(Student::query()->count())->toBe(0);
});

test('a user account can only be linked to one student', function () {
    $account = User::factory()->create();
    Student::factory()->create(['user_id' => $account->id]);

    $this->actingAs($this->administrator)
        ->post(route('school.students.store'), studentPayload([
            'user_id' => $account->id,
        ]))
        ->assertSessionHasErrors('user_id');
});

test('users without the manage permission cannot create or update students', function () {
    Student::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('people.students.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.students.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.students.store'), studentPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.students.update', Student::query()->firstOrFail()), studentPayload())
        ->assertForbidden();
});

test('administrators can update a student', function () {
    $student = Student::factory()->create([
        'first_name' => 'Old',
        'surname' => 'Name',
    ]);
    $family = Family::factory()->create();
    $account = User::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.students.update', $student), studentPayload([
            'first_name' => 'New',
            'surname' => 'Name',
            'gender' => 'Male',
            'family_id' => $family->id,
            'user_id' => $account->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.students.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Student updated.',
        ]);

    $student->refresh();

    expect($student->first_name)->toBe('New')
        ->and($student->gender)->toBe('Male')
        ->and($student->family_id)->toBe($family->id)
        ->and($student->user_id)->toBe($account->id);
});

test('a student can be updated while keeping their linked account', function () {
    $account = User::factory()->create();
    $student = Student::factory()->create(['user_id' => $account->id]);

    $this->actingAs($this->administrator)
        ->put(route('school.students.update', $student), studentPayload([
            'first_name' => 'Renamed',
            'surname' => 'Student',
            'user_id' => $account->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($student->refresh()->user_id)->toBe($account->id);
});

test('administrators can open the student form', function () {
    $family = Family::factory()->create();
    $student = Student::factory()->create([
        'family_id' => $family->id,
        'first_name' => 'Edit',
        'surname' => 'Me',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.students.edit', $student))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/students/Edit')
            ->where('student.first_name', 'Edit')
            ->where('student.family_id', $family->id)
            ->has('families', 1)
            ->has('genders', 4)
            ->has('accounts'));
});
