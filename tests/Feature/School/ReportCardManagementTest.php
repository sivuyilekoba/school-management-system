<?php

use App\Enums\AttendanceStatus;
use App\Models\Assessment;
use App\Models\AttendanceRegister;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\ReportCard;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * Create a school year with a term.
 *
 * @param  array<string, mixed>  $overrides
 */
function reportCardTerm(array $overrides = []): Term
{
    $schoolYear = SchoolYear::query()->firstOrCreate(
        ['name' => '2026-2027'],
        [
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ],
    );

    return $schoolYear->terms()->firstOrCreate(
        ['name' => $overrides['name'] ?? 'Term 1'],
        [
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-04',
            ...$overrides,
        ],
    );
}

/**
 * Create a class with the given students enrolled.
 *
 * @param  iterable<int, Student>  $students
 */
function reportCardClass(iterable $students = []): CourseClass
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
 * Create an assessment with a mark for a student.
 */
function reportCardAssessment(
    CourseClass $courseClass,
    Student $student,
    string $date,
    float $score,
    float $maxScore = 100,
): Assessment {
    $assessment = $courseClass->assessments()->create([
        'name' => 'Assessment '.$date,
        'assessed_on' => $date,
        'max_score' => $maxScore,
    ]);

    $assessment->marks()->create([
        'student_id' => $student->id,
        'score' => $score,
    ]);

    return $assessment;
}

/**
 * Create an attendance register for a class.
 *
 * @param  array<int, array{student: Student, status: AttendanceStatus}>  $marks
 */
function reportCardAttendance(CourseClass $courseClass, string $date, array $marks): void
{
    $register = AttendanceRegister::query()->create([
        'course_class_id' => $courseClass->id,
        'date' => $date,
    ]);

    foreach ($marks as $mark) {
        $register->records()->create([
            'student_id' => $mark['student']->id,
            'status' => $mark['status'],
        ]);
    }
}

test('guests are redirected to the login page', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $this->get(route('school.reports.index'))->assertRedirect(route('login'));
    $this->get(route('school.reports.show', [$student, $term]))->assertRedirect(route('login'));
    $this->get(route('school.reports.print', [$student, $term]))->assertRedirect(route('login'));
    $this->get(route('school.reports.pdf', [$student, $term]))->assertRedirect(route('login'));
});

test('users without the reports view permission cannot view report cards', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.reports.index'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.reports.show', [$student, $term]))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.reports.print', [$student, $term]))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.reports.pdf', [$student, $term]))
        ->assertForbidden();
});

test('users with only the view permission cannot edit or publish report cards', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    Role::findByName('Teacher')->givePermissionTo('reports.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.reports.index'))
        ->assertOk();

    $this->actingAs($teacher)
        ->get(route('school.reports.show', [$student, $term]))
        ->assertOk();

    $this->actingAs($teacher)
        ->put(route('school.reports.comment.update', [$student, $term]), [
            'comment' => 'Trying to write a comment.',
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.reports.publish', [$student, $term]))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('school.reports.unpublish', [$student, $term]))
        ->assertForbidden();
});

test('the report card index summarises the chosen term', function () {
    $term = reportCardTerm();

    $amara = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);
    $zara = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
    ]);

    $courseClass = reportCardClass([$amara, $zara]);

    reportCardAssessment($courseClass, $amara, '2026-10-01', 80);
    reportCardAssessment($courseClass, $zara, '2026-10-01', 60);

    reportCardAssessment($courseClass, $amara, '2027-02-01', 100);

    reportCardAttendance($courseClass, '2026-11-10', [
        ['student' => $amara, 'status' => AttendanceStatus::Present],
        ['student' => $zara, 'status' => AttendanceStatus::Absent],
    ]);

    reportCardAttendance($courseClass, '2027-02-10', [
        ['student' => $amara, 'status' => AttendanceStatus::Present],
    ]);

    ReportCard::factory()->published()->create([
        'student_id' => $amara->id,
        'term_id' => $term->id,
    ]);

    ReportCard::factory()->create([
        'student_id' => $zara->id,
        'term_id' => $term->id,
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.reports.index', ['term' => $term->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/reports/Index')
            ->where('term.id', $term->id)
            ->where('term.name', 'Term 1')
            ->has('students', 2)
            ->where('students.0.name', 'Amara Aardvark')
            ->where('students.0.classes_count', 1)
            ->where('students.0.average', 80)
            ->where('students.0.attendance.present', 1)
            ->where('students.0.attendance.total', 1)
            ->where('students.0.status', 'published')
            ->where('students.1.name', 'Zara Zebra')
            ->where('students.1.average', 60)
            ->where('students.1.attendance.absent', 1)
            ->where('students.1.attendance.total', 1)
            ->where('students.1.status', 'draft'));
});

test('the index defaults to the term containing today and groups terms by year', function () {
    $term = reportCardTerm();

    $schoolYear = $term->schoolYear;
    $schoolYear->terms()->create([
        'name' => 'Term 2',
        'start_date' => '2027-01-12',
        'end_date' => '2027-03-26',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('term.id', $term->id)
            ->has('schoolYears', 1)
            ->where('schoolYears.0.name', '2026-2027')
            ->has('schoolYears.0.terms', 2));
});

test('the report card shows term assessments and attendance only', function () {
    $term = reportCardTerm();

    $student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);
    $otherStudent = Student::factory()->create();

    $mathematics = reportCardClass([$student, $otherStudent]);
    $english = Course::factory()->create(['name' => 'English'])->classes()->create(['name' => 'A']);
    $english->students()->attach($student->id);

    reportCardAssessment($mathematics, $student, '2026-10-01', 80);
    reportCardAssessment($mathematics, $student, '2026-10-15', 45, 50);
    reportCardAssessment($mathematics, $student, '2027-02-01', 100);
    reportCardAssessment($mathematics, $otherStudent, '2026-10-05', 10);
    reportCardAssessment($english, $student, '2026-11-01', 70);

    reportCardAttendance($mathematics, '2026-11-10', [
        ['student' => $student, 'status' => AttendanceStatus::Present],
    ]);
    reportCardAttendance($mathematics, '2026-11-11', [
        ['student' => $student, 'status' => AttendanceStatus::Late],
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.reports.show', [$student, $term]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/reports/Show')
            ->where('term.id', $term->id)
            ->where('student.name', 'Amara Aardvark')
            ->has('classes', 2)
            ->where('classes.0.name', 'English (A)')
            ->where('classes.0.percentage', 70)
            ->has('classes.0.assessments', 1)
            ->where('classes.1.name', 'Mathematics (A)')
            ->where('classes.1.percentage', 83.3)
            ->has('classes.1.assessments', 3)
            ->where('classes.1.assessments.0.score', 80)
            ->where('classes.1.assessments.1.score', null)
            ->where('classes.1.assessments.2.score', 45)
            ->where('overall.percentage', 78)
            ->where('overall.assessments_count', 4)
            ->where('overall.marked_count', 3)
            ->where('attendance.present', 1)
            ->where('attendance.late', 1)
            ->where('attendance.total', 2)
            ->where('reportCard.published', false)
            ->where('reportCard.comment', null));
});

test('a student with no enrolments has an empty report card', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $this->actingAs($this->administrator)
        ->get(route('school.reports.show', [$student, $term]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('classes', 0)
            ->where('overall.percentage', null)
            ->where('overall.assessments_count', 0)
            ->where('attendance.total', 0));
});

test('administrators can save and clear a report card comment', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.reports.comment.update', [$student, $term]), [
            'comment' => 'A strong term.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Report card comment saved.',
        ]);

    $this->assertDatabaseHas('report_cards', [
        'student_id' => $student->id,
        'term_id' => $term->id,
        'comment' => 'A strong term.',
        'published_at' => null,
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.reports.comment.update', [$student, $term]), [
            'comment' => 'An excellent term.',
        ])
        ->assertSessionHasNoErrors();

    expect(ReportCard::query()->count())->toBe(1);

    $this->assertDatabaseHas('report_cards', [
        'student_id' => $student->id,
        'term_id' => $term->id,
        'comment' => 'An excellent term.',
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.reports.comment.update', [$student, $term]), [
            'comment' => null,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('report_cards', [
        'student_id' => $student->id,
        'term_id' => $term->id,
        'comment' => null,
    ]);
});

test('saving a comment validates its length', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.reports.comment.update', [$student, $term]), [
            'comment' => str_repeat('a', 5001),
        ])
        ->assertSessionHasErrors('comment');

    expect(ReportCard::query()->count())->toBe(0);
});

test('administrators can publish and unpublish a report card', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.reports.publish', [$student, $term]))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Report card published.',
        ]);

    $reportCard = ReportCard::query()->firstOrFail();

    expect($reportCard->student_id)->toBe($student->id)
        ->and($reportCard->term_id)->toBe($term->id)
        ->and($reportCard->isPublished())->toBeTrue();

    $this->actingAs($this->administrator)
        ->delete(route('school.reports.unpublish', [$student, $term]))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Report card unpublished.',
        ]);

    expect($reportCard->refresh()->isPublished())->toBeFalse()
        ->and(ReportCard::query()->count())->toBe(1);
});

test('the printable report card shows results and print controls', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);

    $courseClass = reportCardClass([$student]);
    reportCardAssessment($courseClass, $student, '2026-10-01', 80);

    ReportCard::factory()->create([
        'student_id' => $student->id,
        'term_id' => $term->id,
        'comment' => 'A strong term.',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.reports.print', [$student, $term]))
        ->assertOk()
        ->assertSee('Amara Aardvark')
        ->assertSee('Mathematics (A)')
        ->assertSee('80 / 100')
        ->assertSee('A strong term.')
        ->assertSee('window.print()', false);
});

test('the pdf version of a report card uses the print view without browser controls', function () {
    Pdf::fake();

    $term = reportCardTerm();
    $student = Student::factory()->create(['first_name' => 'Amara', 'surname' => 'Aardvark']);

    $this->actingAs($this->administrator)
        ->get(route('school.reports.pdf', [$student, $term]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'school.reports.print'
        && $pdf->viewData['forPdf'] === true);
});

test('a report card can be downloaded as a pdf', function () {
    $term = reportCardTerm();
    $student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);

    $courseClass = reportCardClass([$student]);
    reportCardAssessment($courseClass, $student, '2026-10-01', 80);

    $response = $this->actingAs($this->administrator)
        ->get(route('school.reports.pdf', [$student, $term]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('amara-aardvark-term-1-report-card.pdf');

    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});
