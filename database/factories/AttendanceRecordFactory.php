<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegister;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_register_id' => AttendanceRegister::factory(),
            'student_id' => Student::factory(),
            'status' => AttendanceStatus::Present,
            'note' => null,
        ];
    }
}
