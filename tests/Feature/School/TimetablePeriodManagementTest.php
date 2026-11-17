<?php

use App\Models\TimetablePeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update a timetable period.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function timetablePeriodPayload(array $overrides = []): array
{
    return [
        'name' => 'Period 1',
        'start_time' => '08:00',
        'end_time' => '08:45',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.timetable.periods.index'))->assertRedirect(route('login'));
});

test('users without the manage permission cannot access periods', function () {
    Role::findByName('Teacher')->givePermissionTo('timetable.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.timetable.periods.index'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.timetable.periods.store'), timetablePeriodPayload())
        ->assertForbidden();
});

test('administrators can list periods in time order', function () {
    TimetablePeriod::factory()->create([
        'name' => 'Period 2',
        'start_time' => '09:00',
        'end_time' => '09:45',
    ]);
    TimetablePeriod::factory()->create([
        'name' => 'Period 1',
        'start_time' => '08:00',
        'end_time' => '08:45',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.timetable.periods.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/timetable/periods/Index')
            ->has('periods', 2)
            ->where('periods.0.name', 'Period 1')
            ->where('periods.1.name', 'Period 2'));
});

test('administrators can create a period', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.timetable.periods.store'), timetablePeriodPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.timetable.periods.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Period created.',
        ]);

    $period = TimetablePeriod::query()->firstOrFail();

    expect($period->name)->toBe('Period 1')
        ->and($period->start_time)->toBe('08:00')
        ->and($period->end_time)->toBe('08:45');
});

test('creating a period validates its name and times', function () {
    TimetablePeriod::factory()->create(['name' => 'Period 1']);

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.periods.store'), timetablePeriodPayload([
            'name' => 'Period 1',
            'start_time' => '08:45',
            'end_time' => '08:00',
        ]))
        ->assertSessionHasErrors(['name', 'end_time']);

    expect(TimetablePeriod::query()->count())->toBe(1);
});

test('administrators can update a period', function () {
    $period = TimetablePeriod::factory()->create([
        'name' => 'Period 1',
        'start_time' => '08:00',
        'end_time' => '08:45',
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.timetable.periods.update', $period), timetablePeriodPayload([
            'name' => 'Registration',
            'start_time' => '07:45',
            'end_time' => '08:00',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.timetable.periods.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Period updated.',
        ]);

    expect($period->refresh()->name)->toBe('Registration')
        ->and($period->start_time)->toBe('07:45')
        ->and($period->end_time)->toBe('08:00');
});
