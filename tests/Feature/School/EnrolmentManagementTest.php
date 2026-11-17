<?php

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

test('guests are redirected to the login page', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);

    $this->get(route('school.classes.students.index', $courseClass))
        ->assertRedirect(route('login'));
});

test('users without the curriculum view permission cannot list class students', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.classes.students.index', $courseClass))
        ->assertForbidden();
});

test('users with the view permission can list class students', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A']);
    $enrolled = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Okafor',
    ]);
    $available = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
    ]);
    $courseClass->students()->attach($enrolled->id);

    Role::findByName('Teacher')->givePermissionTo('curriculum.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.classes.students.index', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/courses/students/Index')
            ->where('courseClass.name', 'Mathematics (A)')
            ->where('courseClass.course_id', $course->id)
            ->has('enrolled', 1)
            ->where('enrolled.0.id', $enrolled->id)
            ->has('available', 1)
            ->where('available.0.id', $available->id));
});

test('users with only the view permission cannot enrol or remove students', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $student = Student::factory()->create();
    $courseClass->students()->attach($student->id);

    Role::findByName('Teacher')->givePermissionTo('curriculum.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->post(route('school.classes.students.store', $courseClass), [
            'student_id' => Student::factory()->create()->id,
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('school.classes.students.destroy', [$courseClass, $student]))
        ->assertForbidden();
});

test('administrators can enrol a student in a class', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $student = Student::factory()->create();

    $this->actingAs($this->administrator)
        ->from(route('school.classes.students.index', $courseClass))
        ->post(route('school.classes.students.store', $courseClass), [
            'student_id' => $student->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.classes.students.index', $courseClass))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Student enrolled.',
        ]);

    expect($courseClass->students()->pluck('students.id')->all())->toBe([$student->id]);
});

test('a student cannot be enrolled in the same class twice', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $student = Student::factory()->create();
    $courseClass->students()->attach($student->id);

    $this->actingAs($this->administrator)
        ->post(route('school.classes.students.store', $courseClass), [
            'student_id' => $student->id,
        ])
        ->assertSessionHasErrors('student_id');

    expect(Enrolment::query()->count())->toBe(1);
});

test('enrolling validates the student', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);

    $this->actingAs($this->administrator)
        ->post(route('school.classes.students.store', $courseClass), [
            'student_id' => 9999,
        ])
        ->assertSessionHasErrors('student_id');

    expect(Enrolment::query()->count())->toBe(0);
});

test('available students can be searched and exclude enrolled students', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);

    $enrolled = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
        'email' => 'zara@example.com',
    ]);
    Student::factory()->create([
        'first_name' => 'Yusuf',
        'surname' => 'Yak',
        'email' => 'yusuf@example.com',
    ]);
    Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Okafor',
        'email' => 'amara@example.com',
    ]);
    $courseClass->students()->attach($enrolled->id);

    $this->actingAs($this->administrator)
        ->get(route('school.classes.students.index', [
            'courseClass' => $courseClass,
            'q' => 'Yusuf',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('available', 1)
            ->where('available.0.email', 'yusuf@example.com')
            ->where('search', 'Yusuf'));

    $this->actingAs($this->administrator)
        ->get(route('school.classes.students.index', [
            'courseClass' => $courseClass,
            'q' => 'Zara',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('available', 0)
            ->has('enrolled', 1));
});

test('administrators can remove a student from a class', function () {
    $course = Course::factory()->create();
    $courseClass = $course->classes()->create(['name' => 'A']);
    $student = Student::factory()->create();
    $courseClass->students()->attach($student->id);

    $this->actingAs($this->administrator)
        ->from(route('school.classes.students.index', $courseClass))
        ->delete(route('school.classes.students.destroy', [$courseClass, $student]))
        ->assertRedirect(route('school.classes.students.index', $courseClass))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Student removed from the class.',
        ]);

    expect($courseClass->students()->count())->toBe(0);
});

test('the course page shows how many students are enrolled in each class', function () {
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A']);
    $courseClass->students()->attach(Student::factory()->count(2)->create()->pluck('id')->all());

    $this->actingAs($this->administrator)
        ->get(route('school.courses.edit', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->where('classes.0.students_count', 2));
});
