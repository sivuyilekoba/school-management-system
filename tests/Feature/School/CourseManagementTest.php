<?php

use App\Models\Course;
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
 * The payload used to create or update a course.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function coursePayload(array $overrides = []): array
{
    return [
        'name' => 'Mathematics',
        'short_name' => 'MATH',
        'description' => 'Core mathematics course.',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('school.courses.index'))->assertRedirect(route('login'));
});

test('users without the curriculum permission cannot list courses', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.courses.index'))
        ->assertForbidden();
});

test('users with the view permission can list courses', function () {
    Course::factory()->count(2)->create();

    Role::findByName('Teacher')->givePermissionTo('curriculum.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.courses.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/courses/Index')
            ->has('courses.data', 2)
            ->where('courses.total', 2));
});

test('the course list can be searched by name or short name', function () {
    Course::factory()->create(['name' => 'Mathematics', 'short_name' => 'MATH']);
    Course::factory()->create(['name' => 'English', 'short_name' => 'ENG']);

    $this->actingAs($this->administrator)
        ->get(route('school.courses.index', ['q' => 'MATH']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.name', 'Mathematics')
            ->where('search', 'MATH'));

    $this->actingAs($this->administrator)
        ->get(route('school.courses.index', ['q' => 'English']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.name', 'English'));
});

test('administrators can create a course', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.courses.store'), coursePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.courses.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Course created.',
        ]);

    $course = Course::query()->firstOrFail();

    expect($course->name)->toBe('Mathematics')
        ->and($course->short_name)->toBe('MATH')
        ->and($course->description)->toBe('Core mathematics course.');
});

test('creating a course validates the name', function () {
    Course::factory()->create(['name' => 'Mathematics']);

    $this->actingAs($this->administrator)
        ->post(route('school.courses.store'), coursePayload())
        ->assertSessionHasErrors('name');

    expect(Course::query()->count())->toBe(1);
});

test('users without the manage permission cannot create or update courses', function () {
    $course = Course::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('curriculum.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.courses.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.courses.store'), coursePayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.courses.update', $course), coursePayload())
        ->assertForbidden();
});

test('administrators can update a course', function () {
    $course = Course::factory()->create(['name' => 'Old name', 'short_name' => 'OLD']);

    $this->actingAs($this->administrator)
        ->put(route('school.courses.update', $course), coursePayload([
            'name' => 'Further Mathematics',
            'short_name' => 'FMATH',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.courses.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Course updated.',
        ]);

    expect($course->refresh()->name)->toBe('Further Mathematics')
        ->and($course->short_name)->toBe('FMATH');
});

test('the course edit page lists its classes with teachers', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $teacher = Staff::factory()->create([
        'first_name' => 'Ada',
        'surname' => 'Lovelace',
        'preferred_name' => null,
    ]);
    $course->classes()->create([
        'name' => '10A',
        'staff_id' => $teacher->id,
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.courses.edit', $course))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/courses/Edit')
            ->where('course.name', 'Mathematics')
            ->has('classes', 1)
            ->where('classes.0.name', '10A')
            ->where('classes.0.teacher', 'Ada Lovelace'));
});
