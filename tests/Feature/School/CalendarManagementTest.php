<?php

use App\Models\SchoolYear;
use App\Models\Term;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update a school year.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function schoolYearPayload(array $overrides = []): array
{
    return [
        'name' => '2026-2027',
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
        'is_current' => false,
        ...$overrides,
    ];
}

/**
 * The payload used to create or update a term.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function termPayload(array $overrides = []): array
{
    return [
        'name' => 'Term 1',
        'start_date' => '2026-09-01',
        'end_date' => '2026-12-04',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.calendar.index'))->assertRedirect(route('login'));
});

test('users without the calendar permission cannot list school years', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.calendar.index'))
        ->assertForbidden();
});

test('users with the view permission can list school years', function () {
    SchoolYear::factory()->count(2)->create();

    Role::findByName('Teacher')->givePermissionTo('calendar.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/calendar/Index')
            ->has('years', 2));
});

test('administrators can create a school year', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.calendar.years.store'), schoolYearPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.calendar.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'School year created.',
        ]);

    $year = SchoolYear::query()->firstOrFail();

    expect($year->name)->toBe('2026-2027')
        ->and($year->start_date->toDateString())->toBe('2026-09-01')
        ->and($year->end_date->toDateString())->toBe('2027-06-30')
        ->and($year->is_current)->toBeFalse();
});

test('creating a school year validates the name and dates', function () {
    SchoolYear::factory()->create(['name' => '2026-2027']);

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.years.store'), schoolYearPayload([
            'name' => '2026-2027',
            'end_date' => '2026-08-01',
        ]))
        ->assertSessionHasErrors(['name', 'end_date']);

    expect(SchoolYear::query()->count())->toBe(1);
});

test('marking a school year as current clears the previous current year', function () {
    $previous = SchoolYear::factory()->create([
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-06-30',
        'is_current' => true,
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.years.store'), schoolYearPayload([
            'is_current' => true,
        ]))
        ->assertSessionHasNoErrors();

    expect($previous->refresh()->is_current)->toBeFalse()
        ->and(SchoolYear::query()->where('name', '2026-2027')->firstOrFail()->is_current)->toBeTrue();
});

test('users without the manage permission cannot create or update school years', function () {
    $year = SchoolYear::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('calendar.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.calendar.years.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.calendar.years.store'), schoolYearPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.calendar.years.update', $year), schoolYearPayload())
        ->assertForbidden();
});

test('administrators can update a school year', function () {
    $year = SchoolYear::factory()->create(['name' => '2025-2026']);

    $this->actingAs($this->administrator)
        ->put(route('school.calendar.years.update', $year), schoolYearPayload([
            'name' => '2026-2027',
            'is_current' => true,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.calendar.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'School year updated.',
        ]);

    expect($year->refresh()->name)->toBe('2026-2027')
        ->and($year->is_current)->toBeTrue();
});

test('the school year edit page lists its terms', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);
    $year->terms()->create([
        'name' => 'Term 1',
        'start_date' => '2026-09-01',
        'end_date' => '2026-12-04',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.calendar.years.edit', $year))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/calendar/Edit')
            ->where('year.id', $year->id)
            ->has('terms', 1)
            ->where('terms.0.name', 'Term 1'));
});

test('administrators can create a term within a school year', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.terms.store', $year), termPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.calendar.years.edit', $year))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Term created.',
        ]);

    expect($year->terms()->count())->toBe(1);
});

test('term dates must fall within the school year', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.terms.store', $year), termPayload([
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-01',
        ]))
        ->assertSessionHasErrors(['start_date', 'end_date']);

    expect(Term::query()->count())->toBe(0);
});

test('term names are unique within a school year but can repeat across years', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);
    $year->terms()->create(termPayload());

    $otherYear = SchoolYear::factory()->create([
        'name' => '2027-2028',
        'start_date' => '2027-09-01',
        'end_date' => '2028-06-30',
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.terms.store', $year), termPayload())
        ->assertSessionHasErrors('name');

    $this->actingAs($this->administrator)
        ->post(route('school.calendar.terms.store', $otherYear), termPayload([
            'start_date' => '2027-09-01',
            'end_date' => '2027-12-03',
        ]))
        ->assertSessionHasNoErrors();

    expect($otherYear->terms()->count())->toBe(1);
});

test('administrators can update a term', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);
    $term = $year->terms()->create(termPayload());

    $this->actingAs($this->administrator)
        ->put(route('school.calendar.terms.update', $term), termPayload([
            'name' => 'Autumn term',
            'end_date' => '2026-12-18',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.calendar.years.edit', $year))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Term updated.',
        ]);

    expect($term->refresh()->name)->toBe('Autumn term')
        ->and($term->end_date->toDateString())->toBe('2026-12-18');
});

test('users without the manage permission cannot create or update terms', function () {
    $year = SchoolYear::factory()->create([
        'start_date' => '2026-09-01',
        'end_date' => '2027-06-30',
    ]);
    $term = $year->terms()->create(termPayload());

    Role::findByName('Teacher')->givePermissionTo('calendar.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->post(route('school.calendar.terms.store', $year), termPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.calendar.terms.update', $term), termPayload())
        ->assertForbidden();
});
