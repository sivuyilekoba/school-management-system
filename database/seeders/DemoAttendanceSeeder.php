<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRegister;
use App\Models\CourseClass;
use Illuminate\Database\Seeder;

class DemoAttendanceSeeder extends Seeder
{
    /**
     * Seed today's attendance registers for local development.
     */
    public function run(): void
    {
        $date = now()->toDateString();

        $classes = CourseClass::query()
            ->with('students:id')
            ->orderBy('id')
            ->get()
            ->values();

        foreach ($classes as $classIndex => $courseClass) {
            if ($courseClass->students->isEmpty()) {
                continue;
            }

            $register = AttendanceRegister::query()->firstOrCreate([
                'course_class_id' => $courseClass->id,
                'date' => $date,
            ]);

            foreach ($courseClass->students->values() as $studentIndex => $student) {
                $register->records()->updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'status' => $this->statusFor($classIndex, $studentIndex),
                        'note' => null,
                    ],
                );
            }
        }
    }

    /**
     * Pick a demo status so registers show a realistic mix of marks.
     */
    private function statusFor(int $classIndex, int $studentIndex): AttendanceStatus
    {
        return match (($classIndex + $studentIndex) % 10) {
            3 => AttendanceStatus::Absent,
            7 => AttendanceStatus::Late,
            9 => AttendanceStatus::Excused,
            default => AttendanceStatus::Present,
        };
    }
}
