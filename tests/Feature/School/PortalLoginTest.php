<?php

use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create a portal login.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function portalLoginPayload(array $overrides = []): array
{
    return [
        'email' => 'amara@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $student = Student::factory()->create();
    $family = Family::factory()->create();

    $this->post(route('school.students.login.store', $student), portalLoginPayload())
        ->assertRedirect(route('login'));

    $this->post(route('school.families.login.store', $family), portalLoginPayload())
        ->assertRedirect(route('login'));
});

test('users without the people permissions cannot create logins', function () {
    $student = Student::factory()->create();
    $family = Family::factory()->create();

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->post(route('school.students.login.store', $student), portalLoginPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.families.login.store', $family), portalLoginPayload())
        ->assertForbidden();

    expect(User::query()->where('email', 'amara@example.com')->exists())->toBeFalse()
        ->and($student->refresh()->user_id)->toBeNull()
        ->and($family->refresh()->user_id)->toBeNull();
});

test('administrators can create a student login', function () {
    $student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Okafor',
        'preferred_name' => 'Ama',
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.students.login.store', $student), portalLoginPayload([
            'email' => 'AMARA@EXAMPLE.COM',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Student login created.',
        ]);

    $user = User::query()->where('email', 'amara@example.com')->firstOrFail();

    expect($user->name)->toBe('Ama Okafor')
        ->and($user->hasRole('Student'))->toBeTrue()
        ->and(Hash::check('correct-horse-battery', $user->password))->toBeTrue()
        ->and($student->refresh()->user_id)->toBe($user->id);
});

test('administrators can create a family login', function () {
    $family = Family::factory()->create(['name' => 'The Okafor family']);

    $this->actingAs($this->administrator)
        ->post(route('school.families.login.store', $family), portalLoginPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Family login created.',
        ]);

    $user = User::query()->where('email', 'amara@example.com')->firstOrFail();

    expect($user->name)->toBe('The Okafor family')
        ->and($user->hasRole('Parent'))->toBeTrue()
        ->and($family->refresh()->user_id)->toBe($user->id);
});

test('the edit pages report whether a login already exists', function () {
    $student = Student::factory()->create();
    $family = Family::factory()->create();

    $this->actingAs($this->administrator)
        ->get(route('school.students.edit', $student))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/students/Edit')
            ->where('login.exists', false)
            ->where('login.email', null));

    $this->actingAs($this->administrator)
        ->get(route('school.families.edit', $family))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/families/Edit')
            ->where('family.login.exists', false)
            ->where('family.login.email', null));
});

test('the edit pages show the linked account email', function () {
    $studentUser = User::factory()->create(['name' => 'Amara Okafor', 'email' => 'amara@example.com']);
    $studentUser->assignRole('Student');
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $parentUser = User::factory()->create(['name' => 'The Okafor family', 'email' => 'parent@example.com']);
    $parentUser->assignRole('Parent');
    $family = Family::factory()->create(['user_id' => $parentUser->id]);

    $this->actingAs($this->administrator)
        ->get(route('school.students.edit', $student))
        ->assertInertia(fn (Assert $page) => $page
            ->where('login.exists', true)
            ->where('login.email', 'amara@example.com')
            ->where('login.name', 'Amara Okafor'));

    $this->actingAs($this->administrator)
        ->get(route('school.families.edit', $family))
        ->assertInertia(fn (Assert $page) => $page
            ->where('family.login.exists', true)
            ->where('family.login.email', 'parent@example.com'));
});

test('creating a login validates the email address and password', function () {
    $student = Student::factory()->create();

    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->administrator)
        ->post(route('school.students.login.store', $student), portalLoginPayload([
            'email' => 'taken@example.com',
        ]))
        ->assertSessionHasErrors('email');

    $this->actingAs($this->administrator)
        ->post(route('school.students.login.store', $student), portalLoginPayload([
            'email' => 'not-an-email',
        ]))
        ->assertSessionHasErrors('email');

    $this->actingAs($this->administrator)
        ->post(route('school.students.login.store', $student), portalLoginPayload([
            'password_confirmation' => 'something-else',
        ]))
        ->assertSessionHasErrors('password');

    $this->actingAs($this->administrator)
        ->post(route('school.families.login.store', Family::factory()->create()), portalLoginPayload([
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'taken@example.com')->count())->toBe(1)
        ->and($student->refresh()->user_id)->toBeNull();
});

test('a student or family record can only have one login', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole('Student');
    $student = Student::factory()->create(['user_id' => $studentUser->id]);

    $parentUser = User::factory()->create();
    $parentUser->assignRole('Parent');
    $family = Family::factory()->create(['user_id' => $parentUser->id]);

    $this->actingAs($this->administrator)
        ->post(route('school.students.login.store', $student), portalLoginPayload())
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'This student already has a login account.',
        ]);

    $this->actingAs($this->administrator)
        ->post(route('school.families.login.store', $family), portalLoginPayload())
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'error',
            'message' => 'This family already has a login account.',
        ]);

    expect(User::query()->where('email', 'amara@example.com')->exists())->toBeFalse()
        ->and($student->refresh()->user_id)->toBe($studentUser->id)
        ->and($family->refresh()->user_id)->toBe($parentUser->id);
});
