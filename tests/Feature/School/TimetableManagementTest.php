<?php

use App\Models\Course;
use App\Models\Staff;
use App\Models\TimetableEntry;
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
 * The payload used to create or update a timetable lesson.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function timetableEntryPayload(array $overrides = []): array
{
    return [
        'day_of_week' => 1,
        'timetable_period_id' => null,
        'room' => 'Room 12',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.timetable.index'))->assertRedirect(route('login'));
});

test('users without the timetable permission cannot view the timetable', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.timetable.index'))
        ->assertForbidden();
});

test('users with the view permission can see a class timetable', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $classA = $course->classes()->create(['name' => 'A']);
    $classB = $course->classes()->create(['name' => 'B']);
    $period = TimetablePeriod::factory()->create(['name' => 'Period 1']);

    $classA->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);
    $classB->timetableEntries()->create([
        'day_of_week' => 2,
        'timetable_period_id' => $period->id,
    ]);

    Role::findByName('Teacher')->givePermissionTo('timetable.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.timetable.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/timetable/Index')
            ->has('classes', 2)
            ->where('selectedClass.name', 'Mathematics (A)')
            ->has('periods', 1)
            ->has('entries', 1)
            ->where('entries.0.day_of_week', 1)
            ->has('days', 5));

    $this->actingAs($teacher)
        ->get(route('school.timetable.index', ['class' => $classB->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedClass.name', 'Mathematics (B)')
            ->has('entries', 1)
            ->where('entries.0.day_of_week', 2));
});

test('the timetable page shows an empty state without classes', function () {
    $this->actingAs($this->administrator)
        ->get(route('school.timetable.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classes', 0)
            ->where('selectedClass', null)
            ->has('entries', 0));
});

test('users without the manage permission cannot change the timetable', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $period = TimetablePeriod::factory()->create();
    $entry = $courseClass->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);

    Role::findByName('Teacher')->givePermissionTo('timetable.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.timetable.entries.create', $courseClass))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.timetable.entries.store', $courseClass), timetableEntryPayload([
            'timetable_period_id' => $period->id,
        ]))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.timetable.entries.update', $entry), timetableEntryPayload([
            'timetable_period_id' => $period->id,
        ]))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('school.timetable.entries.destroy', $entry))
        ->assertForbidden();
});

test('administrators can add a lesson to a class timetable', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A']);
    $period = TimetablePeriod::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.entries.store', $courseClass), timetableEntryPayload([
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.timetable.index', ['class' => $courseClass->id]))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Lesson added to the timetable.',
        ]);

    $entry = TimetableEntry::query()->firstOrFail();

    expect($entry->course_class_id)->toBe($courseClass->id)
        ->and($entry->day_of_week)->toBe(1)
        ->and($entry->timetable_period_id)->toBe($period->id)
        ->and($entry->room)->toBe('Room 12');
});

test('adding a lesson validates the day, period and room', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.entries.store', $courseClass), timetableEntryPayload([
            'day_of_week' => 9,
            'timetable_period_id' => 9999,
            'room' => str_repeat('a', 256),
        ]))
        ->assertSessionHasErrors(['day_of_week', 'timetable_period_id', 'room']);

    expect(TimetableEntry::query()->count())->toBe(0);
});

test('a class cannot be scheduled twice in the same slot', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $period = TimetablePeriod::factory()->create();
    $courseClass->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.entries.store', $courseClass), timetableEntryPayload([
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasErrors('timetable_period_id');

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.entries.store', $courseClass), timetableEntryPayload([
            'day_of_week' => 2,
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($courseClass->timetableEntries()->count())->toBe(2);
});

test('a teacher cannot be scheduled for two classes at the same time', function () {
    $teacher = Staff::factory()->create();
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $classA = $course->classes()->create(['name' => 'A', 'staff_id' => $teacher->id]);
    $classB = $course->classes()->create(['name' => 'B', 'staff_id' => $teacher->id]);
    $period = TimetablePeriod::factory()->create();

    $classB->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);

    $this->actingAs($this->administrator)
        ->post(route('school.timetable.entries.store', $classA), timetableEntryPayload([
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasErrors([
            'timetable_period_id' => 'This teacher is already teaching another class at that time.',
        ]);

    expect(TimetableEntry::query()->count())->toBe(1);
});

test('administrators can update a lesson without clashing with itself', function () {
    $teacher = Staff::factory()->create();
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A', 'staff_id' => $teacher->id]);
    $period = TimetablePeriod::factory()->create();
    $entry = $courseClass->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
        'room' => 'Room 1',
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.timetable.entries.update', $entry), timetableEntryPayload([
            'timetable_period_id' => $period->id,
            'room' => 'Room 2',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.timetable.index', ['class' => $courseClass->id]))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Lesson updated.',
        ]);

    expect($entry->refresh()->room)->toBe('Room 2');
});

test('a lesson cannot be moved onto a slot the teacher already teaches', function () {
    $teacher = Staff::factory()->create();
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $classA = $course->classes()->create(['name' => 'A', 'staff_id' => $teacher->id]);
    $classB = $course->classes()->create(['name' => 'B', 'staff_id' => $teacher->id]);
    $period = TimetablePeriod::factory()->create();

    $entry = $classA->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);
    $classB->timetableEntries()->create([
        'day_of_week' => 2,
        'timetable_period_id' => $period->id,
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.timetable.entries.update', $entry), timetableEntryPayload([
            'day_of_week' => 2,
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasErrors('timetable_period_id');

    $this->actingAs($this->administrator)
        ->put(route('school.timetable.entries.update', $entry), timetableEntryPayload([
            'day_of_week' => 3,
            'timetable_period_id' => $period->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($entry->refresh()->day_of_week)->toBe(3);
});

test('administrators can remove a lesson', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $period = TimetablePeriod::factory()->create();
    $entry = $courseClass->timetableEntries()->create([
        'day_of_week' => 1,
        'timetable_period_id' => $period->id,
    ]);

    $this->actingAs($this->administrator)
        ->delete(route('school.timetable.entries.destroy', $entry))
        ->assertRedirect(route('school.timetable.index', ['class' => $courseClass->id]))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Lesson removed.',
        ]);

    expect(TimetableEntry::query()->count())->toBe(0);
});

test('the lesson form is prefilled from the timetable grid', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A']);
    $period = TimetablePeriod::factory()->create();

    $this->actingAs($this->administrator)
        ->get(route('school.timetable.entries.create', [
            'courseClass' => $courseClass,
            'day' => 3,
            'period' => $period->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/timetable/entries/Create')
            ->where('courseClass.name', 'Mathematics (A)')
            ->where('defaults.day_of_week', 3)
            ->where('defaults.timetable_period_id', $period->id)
            ->has('periods', 1)
            ->has('days', 5));
});
