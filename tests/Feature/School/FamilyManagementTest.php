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
 * The payload used to create or update a family.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function familyPayload(array $overrides = []): array
{
    return [
        'name' => 'The Okafor family',
        'contact_email' => 'family@example.com',
        'contact_phone' => '+1 555 0120',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.families.index'))->assertRedirect(route('login'));
});

test('users without the families permission cannot list families', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.families.index'))
        ->assertForbidden();
});

test('users with the view permission can list families', function () {
    $family = Family::factory()->create();
    Student::factory()->count(2)->create(['family_id' => $family->id]);

    Role::findByName('Teacher')->givePermissionTo('people.families.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.families.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/families/Index')
            ->has('families.data', 1)
            ->where('families.data.0.students_count', 2));
});

test('the family list can be searched by name or contact email', function () {
    Family::factory()->create([
        'name' => 'Zebra Family',
        'contact_email' => 'zebra@example.com',
    ]);
    Family::factory()->create([
        'name' => 'Yak Family',
        'contact_email' => 'yak@example.com',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.families.index', ['q' => 'Zebra']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('families.data', 1)
            ->where('families.data.0.name', 'Zebra Family')
            ->where('search', 'Zebra'));

    $this->actingAs($this->administrator)
        ->get(route('school.families.index', ['q' => 'yak@']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('families.data', 1)
            ->where('families.data.0.contact_email', 'yak@example.com'));
});

test('administrators can create a family', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.families.store'), familyPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.families.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Family created.',
        ]);

    $family = Family::query()->firstOrFail();

    expect($family->name)->toBe('The Okafor family')
        ->and($family->contact_email)->toBe('family@example.com')
        ->and($family->contact_phone)->toBe('+1 555 0120');
});

test('creating a family requires a name and a valid contact email', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.families.store'), familyPayload([
            'name' => '',
            'contact_email' => 'not-an-email',
        ]))
        ->assertSessionHasErrors(['name', 'contact_email']);

    expect(Family::query()->count())->toBe(0);
});

test('users without the manage permission cannot create or update families', function () {
    $family = Family::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('people.families.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.families.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.families.store'), familyPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.families.update', $family), familyPayload())
        ->assertForbidden();
});

test('administrators can update a family', function () {
    $family = Family::factory()->create(['name' => 'Old Family']);

    $this->actingAs($this->administrator)
        ->put(route('school.families.update', $family), familyPayload([
            'name' => 'New Family',
            'contact_phone' => '',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.families.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Family updated.',
        ]);

    $family->refresh();

    expect($family->name)->toBe('New Family')
        ->and($family->contact_phone)->toBeNull();
});

test('the family form lists the students in the family', function () {
    $family = Family::factory()->create();
    Student::factory()->create([
        'family_id' => $family->id,
        'first_name' => 'Amara',
        'surname' => 'Okafor',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.families.edit', $family))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/families/Edit')
            ->where('family.name', $family->name)
            ->has('family.students', 1)
            ->where('family.students.0.name', 'Amara Okafor'));
});
