<?php

namespace App\Http\Controllers\School;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\SaveAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\CourseClass;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Show attendance for every class on a date.
     */
    public function index(Request $request): Response
    {
        $date = $this->resolveDate($request->query('date'));

        $classes = CourseClass::query()
            ->with(['course:id,name', 'staff:id,first_name,surname,preferred_name'])
            ->withCount('students')
            ->get()
            ->sortBy(fn (CourseClass $courseClass): string => $courseClass->displayName())
            ->values();

        $registers = AttendanceRegister::query()
            ->whereDate('date', $date->toDateString())
            ->whereIn('course_class_id', $classes->modelKeys())
            ->with('records:id,attendance_register_id,status')
            ->get()
            ->keyBy('course_class_id');

        return Inertia::render('school/attendance/Index', [
            'date' => $date->toDateString(),
            'classes' => $classes
                ->map(function (CourseClass $courseClass) use ($registers): array {
                    $register = $registers->get($courseClass->id);

                    return [
                        'id' => $courseClass->id,
                        'name' => $courseClass->displayName(),
                        'teacher' => $courseClass->staff?->displayName(),
                        'students_count' => $courseClass->students_count,
                        'recorded' => $register instanceof AttendanceRegister,
                        'counts' => $this->statusCounts(
                            $register instanceof AttendanceRegister ? $register->records : collect(),
                        ),
                    ];
                })
                ->values(),
        ]);
    }

    /**
     * Show the register for a class on a date.
     */
    public function show(Request $request, CourseClass $courseClass): Response
    {
        $courseClass->loadMissing(['course', 'staff']);
        $date = $this->resolveDate($request->query('date'));

        $register = AttendanceRegister::query()
            ->where('course_class_id', $courseClass->id)
            ->whereDate('date', $date->toDateString())
            ->with('records')
            ->first();

        $marks = $register instanceof AttendanceRegister
            ? $register->records->keyBy('student_id')
            : collect();

        $students = $courseClass->students()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $student) use ($marks): array {
                $record = $marks->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->displayName(),
                    'status' => $record instanceof AttendanceRecord
                        ? $record->status->value
                        : AttendanceStatus::Present->value,
                    'note' => $record instanceof AttendanceRecord ? $record->note : null,
                ];
            })
            ->values();

        return Inertia::render('school/attendance/Show', [
            'courseClass' => [
                'id' => $courseClass->id,
                'name' => $courseClass->displayName(),
                'teacher' => $courseClass->staff?->displayName(),
            ],
            'date' => $date->toDateString(),
            'students' => $students,
            'statuses' => AttendanceStatus::options(),
            'recorded' => $register instanceof AttendanceRegister,
            'counts' => $this->statusCounts(
                $register instanceof AttendanceRegister ? $register->records : collect(),
            ),
        ]);
    }

    /**
     * Save the register for a class on a date.
     */
    public function save(SaveAttendanceRequest $request, CourseClass $courseClass): RedirectResponse
    {
        $date = $this->resolveDate($request->attendanceDate());
        $records = $request->attendanceRecords();

        DB::transaction(function () use ($courseClass, $date, $records): void {
            $register = AttendanceRegister::query()
                ->where('course_class_id', $courseClass->id)
                ->whereDate('date', $date->toDateString())
                ->first();

            if (! $register instanceof AttendanceRegister) {
                $register = AttendanceRegister::query()->create([
                    'course_class_id' => $courseClass->id,
                    'date' => $date->toDateString(),
                ]);
            }

            $studentIds = $courseClass->students()->pluck('students.id')->all();
            $submitted = collect($records)->keyBy('student_id');

            $register->records()
                ->whereNotIn('student_id', $studentIds)
                ->delete();

            foreach ($studentIds as $studentId) {
                $record = $submitted->get($studentId);

                if ($record === null) {
                    continue;
                }

                $register->records()->updateOrCreate(
                    ['student_id' => $studentId],
                    [
                        'status' => $record['status'],
                        'note' => $record['note'] ?? null,
                    ],
                );
            }
        });

        return to_route('school.attendance.show', [
            'courseClass' => $courseClass->id,
            'date' => $date->toDateString(),
        ])->with('toast', [
            'type' => 'success',
            'message' => 'Attendance saved.',
        ]);
    }

    /**
     * Resolve a date query value, falling back to today.
     */
    private function resolveDate(mixed $value): CarbonImmutable
    {
        if (is_string($value) && CarbonImmutable::hasFormat($value, 'Y-m-d')) {
            return CarbonImmutable::parse($value)->startOfDay();
        }

        return CarbonImmutable::now()->startOfDay();
    }

    /**
     * Count the attendance marks by status.
     *
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array<string, int>
     */
    private function statusCounts(Collection $records): array
    {
        $counts = [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Absent->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::Excused->value => 0,
        ];

        foreach ($records as $record) {
            $counts[$record->status->value]++;
        }

        return $counts;
    }
}
