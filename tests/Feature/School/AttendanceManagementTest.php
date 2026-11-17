<?php

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
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
function attendanceClass(iterable $students = []): CourseClass
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

test('guests are redirected to the login page', function () {
    $courseClass = attendanceClass();

    $this->get(route('school.attendance.index'))->assertRedirect(route('login'));
    $this->get(route('school.attendance.show', $courseClass))->assertRedirect(route('login'));
});

test('users without the attendance view permission cannot view attendance', function () {
    $courseClass = attendanceClass();

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.attendance.index'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.attendance.show', $courseClass))
        ->assertForbidden();
});

test('users with the view permission can see attendance without recording it', function () {
    $student = Student::factory()->create();
    $courseClass = attendanceClass([$student]);

    Role::findByName('Teacher')->givePermissionTo('attendance.view');
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/attendance/Index')
            ->has('classes', 1)
            ->where('classes.0.recorded', false));

    $this->actingAs($teacher)
        ->get(route('school.attendance.show', $courseClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/attendance/Show')
            ->has('students', 1)
            ->where('students.0.status', AttendanceStatus::Present->value));

    $this->actingAs($teacher)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => '2026-11-17',
            'records' => [
                ['student_id' => $student->id, 'status' => 'present'],
            ],
        ])
        ->assertForbidden();
});

test('the daily overview shows class attendance for a date', function () {
    $students = Student::factory()->count(2)->create();
    $courseClass = attendanceClass($students);

    $register = $courseClass->attendanceRegisters()->create(['date' => '2026-11-17']);
    $register->records()->create([
        'student_id' => $students[0]->id,
        'status' => AttendanceStatus::Present,
    ]);
    $register->records()->create([
        'student_id' => $students[1]->id,
        'status' => AttendanceStatus::Absent,
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.attendance.index', ['date' => '2026-11-17']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/attendance/Index')
            ->where('date', '2026-11-17')
            ->has('classes', 1)
            ->where('classes.0.name', 'Mathematics (A)')
            ->where('classes.0.students_count', 2)
            ->where('classes.0.recorded', true)
            ->where('classes.0.counts.present', 1)
            ->where('classes.0.counts.absent', 1)
            ->where('classes.0.counts.late', 0)
            ->where('classes.0.counts.excused', 0));

    $this->actingAs($this->administrator)
        ->get(route('school.attendance.index', ['date' => '2026-11-18']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-11-18')
            ->where('classes.0.recorded', false)
            ->where('classes.0.counts.present', 0));
});

test('the register shows existing marks and defaults unmarked students to present', function () {
    $absent = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
    ]);
    $unmarked = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
    ]);
    $courseClass = attendanceClass([$absent, $unmarked]);

    $register = $courseClass->attendanceRegisters()->create(['date' => '2026-11-17']);
    $register->records()->create([
        'student_id' => $absent->id,
        'status' => AttendanceStatus::Absent,
        'note' => 'Sick at home',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.attendance.show', [
            'courseClass' => $courseClass,
            'date' => '2026-11-17',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/attendance/Show')
            ->where('date', '2026-11-17')
            ->where('recorded', true)
            ->has('students', 2)
            ->where('students.0.name', 'Amara Aardvark')
            ->where('students.0.status', AttendanceStatus::Absent->value)
            ->where('students.0.note', 'Sick at home')
            ->where('students.1.status', AttendanceStatus::Present->value)
            ->where('students.1.note', null)
            ->where('counts.absent', 1)
            ->has('statuses', 4));
});

test('administrators can save a register for the class roster', function () {
    $present = Student::factory()->create();
    $absent = Student::factory()->create();
    $courseClass = attendanceClass([$present, $absent]);

    $this->actingAs($this->administrator)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => '2026-11-17',
            'records' => [
                ['student_id' => $present->id, 'status' => 'present'],
                ['student_id' => $absent->id, 'status' => 'absent', 'note' => 'Sick'],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.attendance.show', [
            'courseClass' => $courseClass,
            'date' => '2026-11-17',
        ]))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Attendance saved.',
        ]);

    $this->assertDatabaseHas('attendance_registers', [
        'course_class_id' => $courseClass->id,
        'date' => '2026-11-17',
    ]);

    $register = AttendanceRegister::query()->firstOrFail();

    $this->assertDatabaseHas('attendance_records', [
        'attendance_register_id' => $register->id,
        'student_id' => $present->id,
        'status' => AttendanceStatus::Present->value,
        'note' => null,
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'attendance_register_id' => $register->id,
        'student_id' => $absent->id,
        'status' => AttendanceStatus::Absent->value,
        'note' => 'Sick',
    ]);
});

test('saving attendance updates existing marks for the same date', function () {
    $student = Student::factory()->create();
    $courseClass = attendanceClass([$student]);

    $register = $courseClass->attendanceRegisters()->create(['date' => '2026-11-17']);
    $register->records()->create([
        'student_id' => $student->id,
        'status' => AttendanceStatus::Present,
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => '2026-11-17',
            'records' => [
                ['student_id' => $student->id, 'status' => 'late', 'note' => 'Traffic'],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(AttendanceRegister::query()->count())->toBe(1)
        ->and(AttendanceRecord::query()->count())->toBe(1);

    $this->assertDatabaseHas('attendance_records', [
        'attendance_register_id' => $register->id,
        'student_id' => $student->id,
        'status' => AttendanceStatus::Late->value,
        'note' => 'Traffic',
    ]);
});

test('saving attendance removes marks for students no longer enrolled', function () {
    $remaining = Student::factory()->create();
    $removed = Student::factory()->create();
    $courseClass = attendanceClass([$remaining, $removed]);

    $register = $courseClass->attendanceRegisters()->create(['date' => '2026-11-17']);
    $register->records()->create([
        'student_id' => $remaining->id,
        'status' => AttendanceStatus::Present,
    ]);
    $register->records()->create([
        'student_id' => $removed->id,
        'status' => AttendanceStatus::Present,
    ]);

    $courseClass->students()->detach($removed->id);

    $this->actingAs($this->administrator)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => '2026-11-17',
            'records' => [
                ['student_id' => $remaining->id, 'status' => 'present'],
            ],
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('attendance_records', [
        'attendance_register_id' => $register->id,
        'student_id' => $removed->id,
    ]);
    $this->assertDatabaseHas('attendance_records', [
        'attendance_register_id' => $register->id,
        'student_id' => $remaining->id,
    ]);
});

test('saving attendance validates the date, roster and statuses', function () {
    $enrolled = Student::factory()->create();
    $outsider = Student::factory()->create();
    $courseClass = attendanceClass([$enrolled]);

    $this->actingAs($this->administrator)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => 'not-a-date',
            'records' => [
                ['student_id' => $outsider->id, 'status' => 'unknown'],
                ['student_id' => $outsider->id, 'status' => 'present'],
            ],
        ])
        ->assertSessionHasErrors([
            'date',
            'records.0.student_id',
            'records.0.status',
            'records.1.student_id',
        ]);

    expect(AttendanceRegister::query()->count())->toBe(0)
        ->and(AttendanceRecord::query()->count())->toBe(0);
});

test('saving attendance requires a record permission', function () {
    $student = Student::factory()->create();
    $courseClass = attendanceClass([$student]);

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->put(route('school.attendance.save', $courseClass), [
            'date' => '2026-11-17',
            'records' => [
                ['student_id' => $student->id, 'status' => 'present'],
            ],
        ])
        ->assertForbidden();

    expect(AttendanceRegister::query()->count())->toBe(0);
});
