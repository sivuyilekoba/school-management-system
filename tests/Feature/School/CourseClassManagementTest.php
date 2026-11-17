<?php

use App\Models\Course;
use App\Models\CourseClass;
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
 * The payload used to create or update a course class.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function courseClassPayload(array $overrides = []): array
{
    return [
        'name' => '10A',
        'staff_id' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $course = Course::factory()->create();

    $this->get(route('school.classes.create', $course))
        ->assertRedirect(route('login'));
});

test('users without the manage permission cannot create or update classes', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(courseClassPayload());

    Role::findByName('Teacher')->givePermissionTo('curriculum.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.classes.create', $course))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.classes.store', $course), courseClassPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.classes.update', $courseClass), courseClassPayload())
        ->assertForbidden();
});

test('administrators can create a class with a teacher', function () {
    $course = Course::factory()->create();
    $teacher = Staff::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.classes.store', $course), courseClassPayload([
            'staff_id' => $teacher->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.courses.edit', $course))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Class created.',
        ]);

    $courseClass = CourseClass::query()->firstOrFail();

    expect($courseClass->course_id)->toBe($course->id)
        ->and($courseClass->name)->toBe('10A')
        ->and($courseClass->staff_id)->toBe($teacher->id)
        ->and($courseClass->displayName())->toBe($course->name.' (10A)');
});

test('a teacher is optional when creating a class', function () {
    $course = Course::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.classes.store', $course), courseClassPayload())
        ->assertSessionHasNoErrors();

    expect(CourseClass::query()->firstOrFail()->staff_id)->toBeNull();
});

test('class names are unique within a course but can repeat across courses', function () {
    $course = Course::factory()->create();
    $course->classes()->create(courseClassPayload());

    $otherCourse = Course::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.classes.store', $course), courseClassPayload())
        ->assertSessionHasErrors('name');

    $this->actingAs($this->administrator)
        ->post(route('school.classes.store', $otherCourse), courseClassPayload())
        ->assertSessionHasNoErrors();

    expect($otherCourse->classes()->count())->toBe(1);
});

test('creating a class validates the teacher', function () {
    $course = Course::factory()->create();

    $this->actingAs($this->administrator)
        ->post(route('school.classes.store', $course), courseClassPayload([
            'name' => '',
            'staff_id' => 9999,
        ]))
        ->assertSessionHasErrors(['name', 'staff_id']);

    expect(CourseClass::query()->count())->toBe(0);
});

test('administrators can update a class and change its teacher', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(courseClassPayload());
    $teacher = Staff::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.classes.update', $courseClass), courseClassPayload([
            'name' => '10B',
            'staff_id' => $teacher->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.courses.edit', $course))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Class updated.',
        ]);

    expect($courseClass->refresh()->name)->toBe('10B')
        ->and($courseClass->staff_id)->toBe($teacher->id);
});

test('the class edit page includes the course and staff options', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(courseClassPayload());
    Staff::factory()->create();

    $this->actingAs($this->administrator)
        ->get(route('school.classes.edit', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/courses/classes/Edit')
            ->where('courseClass.name', '10A')
            ->where('course.name', 'Mathematics')
            ->has('staff'));
});
