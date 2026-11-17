<?php

use App\Models\Assessment;
use App\Models\AssessmentMark;
use App\Models\Course;
use App\Models\CourseClass;
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
 * Create a class with the given students enrolled.
 *
 * @param  iterable<int, Student>  $students
 */
function assessmentClass(iterable $students = []): CourseClass
{
    $course = Course::factory()->create(['name' => 'Mathematics']);
    $courseClass = $course->classes()->create(['name' => 'A']);

    $studentIds = [];

    foreach ($students as $student) {
        $studentIds[] = $student->id;
    }

    if ($studentIds !== []) {
        $courseClass->students()->attach($studentIds);
    }

    return $courseClass;
}

/**
 * Create an assessment for a class.
 *
 * @param  array<string, mixed>  $overrides
 */
function assessmentFor(CourseClass $courseClass, array $overrides = []): Assessment
{
    return $courseClass->assessments()->create([
        'name' => 'Term 1 test',
        'assessed_on' => '2026-11-17',
        'max_score' => 100,
        ...$overrides,
    ]);
}

test('guests are redirected to the login page', function () {
    $courseClass = assessmentClass();
    $assessment = assessmentFor($courseClass);

    $this->get(route('school.assessment.index'))->assertRedirect(route('login'));
    $this->get(route('school.assessment.classes.index', $courseClass))->assertRedirect(route('login'));
    $this->get(route('school.assessment.marks', $assessment))->assertRedirect(route('login'));
});

test('users without the assessment view permission cannot view assessments', function () {
    $courseClass = assessmentClass();
    $assessment = assessmentFor($courseClass);

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.assessment.index'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.assessment.classes.index', $courseClass))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.assessment.marks', $assessment))
        ->assertForbidden();
});

test('users with only the view permission cannot change assessments or marks', function () {
    $student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);
    $courseClass = assessmentClass([$student]);
    $assessment = assessmentFor($courseClass);
    $assessment->marks()->create([
        'student_id' => $student->id,
        'score' => 85,
    ]);

    Role::findByName('Teacher')->givePermissionTo('assessment.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.assessment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/Index')
            ->has('classes', 1)
            ->where('classes.0.assessments_count', 1));

    $this->actingAs($teacher)
        ->get(route('school.assessment.classes.index', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/classes/Index')
            ->has('assessments', 1)
            ->where('assessments.0.marks_count', 1)
            ->where('students_count', 1));

    $this->actingAs($teacher)
        ->get(route('school.assessment.marks', $assessment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/assessments/Marks')
            ->where('students.0.score', '85.00'));

    $this->actingAs($teacher)
        ->post(route('school.assessment.assessments.store', $courseClass), [
            'name' => 'New test',
            'assessed_on' => '2026-11-18',
            'max_score' => 50,
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.assessment.assessments.update', $assessment), [
            'name' => 'Changed',
            'assessed_on' => '2026-11-18',
            'max_score' => 50,
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('school.assessment.assessments.destroy', $assessment))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $student->id, 'score' => 70],
            ],
        ])
        ->assertForbidden();
});

test('the assessment overview lists classes with assessment counts', function () {
    $courseClass = assessmentClass();
    assessmentFor($courseClass, ['name' => 'Term 1 test']);
    assessmentFor($courseClass, ['name' => 'Term 1 assignment', 'max_score' => 50]);

    $this->actingAs($this->administrator)
        ->get(route('school.assessment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/Index')
            ->has('classes', 1)
            ->where('classes.0.name', 'Mathematics (A)')
            ->where('classes.0.students_count', 0)
            ->where('classes.0.assessments_count', 2));
});

test('the class page lists its assessments and mark counts', function () {
    $students = Student::factory()->count(2)->create();
    $courseClass = assessmentClass($students);

    $test = assessmentFor($courseClass, [
        'name' => 'Term 1 test',
        'assessed_on' => '2026-11-17',
    ]);
    assessmentFor($courseClass, [
        'name' => 'Term 1 assignment',
        'assessed_on' => '2026-11-10',
        'max_score' => 50,
    ]);

    $test->marks()->create([
        'student_id' => $students[0]->id,
        'score' => 82,
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.assessment.classes.index', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/classes/Index')
            ->where('courseClass.name', 'Mathematics (A)')
            ->where('students_count', 2)
            ->has('assessments', 2)
            ->where('assessments.0.name', 'Term 1 test')
            ->where('assessments.0.assessed_on', '2026-11-17')
            ->where('assessments.0.max_score', 100)
            ->where('assessments.0.marks_count', 1)
            ->where('assessments.1.marks_count', 0));
});

test('the create form is prefilled with today', function () {
    $courseClass = assessmentClass();

    $this->actingAs($this->administrator)
        ->get(route('school.assessment.assessments.create', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/assessments/Create')
            ->where('courseClass.name', 'Mathematics (A)')
            ->where('defaults.assessed_on', now()->toDateString()));
});

test('administrators can create an assessment', function () {
    $courseClass = assessmentClass();

    $this->actingAs($this->administrator)
        ->post(route('school.assessment.assessments.store', $courseClass), [
            'name' => 'Term 1 test',
            'assessed_on' => '2026-11-17',
            'max_score' => 100,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.assessment.classes.index', $courseClass))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Assessment created.',
        ]);

    $this->assertDatabaseHas('assessments', [
        'course_class_id' => $courseClass->id,
        'name' => 'Term 1 test',
        'assessed_on' => '2026-11-17',
        'max_score' => 100,
    ]);
});

test('creating an assessment validates its name, date and maximum score', function () {
    $courseClass = assessmentClass();

    $this->actingAs($this->administrator)
        ->post(route('school.assessment.assessments.store', $courseClass), [
            'name' => '',
            'assessed_on' => 'not-a-date',
            'max_score' => 0,
        ])
        ->assertSessionHasErrors(['name', 'assessed_on', 'max_score']);

    $this->actingAs($this->administrator)
        ->post(route('school.assessment.assessments.store', $courseClass), [
            'name' => 'Too large',
            'assessed_on' => '2026-11-17',
            'max_score' => 10000,
        ])
        ->assertSessionHasErrors('max_score');

    expect(Assessment::query()->count())->toBe(0);
});

test('administrators can update an assessment', function () {
    $courseClass = assessmentClass();
    $assessment = assessmentFor($courseClass);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.assessments.update', $assessment), [
            'name' => 'Term 1 resit',
            'assessed_on' => '2026-11-20',
            'max_score' => 80,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.assessment.classes.index', $courseClass))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Assessment updated.',
        ]);

    $assessment->refresh();

    expect($assessment->name)->toBe('Term 1 resit')
        ->and($assessment->assessed_on->toDateString())->toBe('2026-11-20')
        ->and($assessment->max_score)->toBe('80.00');
});

test('administrators can remove an assessment and its marks', function () {
    $student = Student::factory()->create();
    $courseClass = assessmentClass([$student]);
    $assessment = assessmentFor($courseClass);
    $assessment->marks()->create([
        'student_id' => $student->id,
        'score' => 75,
    ]);

    $this->actingAs($this->administrator)
        ->delete(route('school.assessment.assessments.destroy', $assessment))
        ->assertRedirect(route('school.assessment.classes.index', $courseClass))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Assessment removed.',
        ]);

    $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    $this->assertDatabaseMissing('assessment_marks', ['assessment_id' => $assessment->id]);
});

test('the marks page lists the roster with existing scores', function () {
    $amara = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);
    $zara = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
    ]);
    $courseClass = assessmentClass([$amara, $zara]);
    $assessment = assessmentFor($courseClass);
    $assessment->marks()->create([
        'student_id' => $amara->id,
        'score' => 85,
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.assessment.marks', $assessment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/assessment/assessments/Marks')
            ->where('assessment.max_score', 100)
            ->has('students', 2)
            ->where('students.0.name', 'Amara Aardvark')
            ->where('students.0.score', '85.00')
            ->where('students.1.name', 'Zara Zebra')
            ->where('students.1.score', ''));
});

test('administrators can save, update and clear assessment marks', function () {
    $first = Student::factory()->create();
    $second = Student::factory()->create();
    $courseClass = assessmentClass([$first, $second]);
    $assessment = assessmentFor($courseClass);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $first->id, 'score' => 80],
                ['student_id' => $second->id, 'score' => null],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.assessment.marks', $assessment))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Marks saved.',
        ]);

    $this->assertDatabaseHas('assessment_marks', [
        'assessment_id' => $assessment->id,
        'student_id' => $first->id,
        'score' => 80,
    ]);
    $this->assertDatabaseMissing('assessment_marks', [
        'assessment_id' => $assessment->id,
        'student_id' => $second->id,
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $first->id, 'score' => 92.5],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(AssessmentMark::query()->count())->toBe(1);

    $this->assertDatabaseHas('assessment_marks', [
        'assessment_id' => $assessment->id,
        'student_id' => $first->id,
        'score' => 92.5,
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $first->id, 'score' => null],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(AssessmentMark::query()->count())->toBe(0);
});

test('saving marks removes marks for students no longer enrolled', function () {
    $remaining = Student::factory()->create();
    $removed = Student::factory()->create();
    $courseClass = assessmentClass([$remaining, $removed]);
    $assessment = assessmentFor($courseClass);

    $assessment->marks()->create([
        'student_id' => $remaining->id,
        'score' => 70,
    ]);
    $assessment->marks()->create([
        'student_id' => $removed->id,
        'score' => 60,
    ]);

    $courseClass->students()->detach($removed->id);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $remaining->id, 'score' => 75],
            ],
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assessment_marks', [
        'assessment_id' => $assessment->id,
        'student_id' => $remaining->id,
        'score' => 75,
    ]);
    $this->assertDatabaseMissing('assessment_marks', [
        'assessment_id' => $assessment->id,
        'student_id' => $removed->id,
    ]);
});

test('saving marks validates the roster, duplicates and maximum score', function () {
    $enrolled = Student::factory()->create();
    $outsider = Student::factory()->create();
    $courseClass = assessmentClass([$enrolled]);
    $assessment = assessmentFor($courseClass);

    $this->actingAs($this->administrator)
        ->put(route('school.assessment.marks.save', $assessment), [
            'records' => [
                ['student_id' => $outsider->id, 'score' => 50],
                ['student_id' => $enrolled->id, 'score' => 150],
                ['student_id' => $enrolled->id, 'score' => 70],
            ],
        ])
        ->assertSessionHasErrors([
            'records.0.student_id',
            'records.1.score',
            'records.2.student_id',
        ]);

    expect(AssessmentMark::query()->count())->toBe(0);
});
