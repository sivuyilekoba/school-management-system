<?php

namespace Database\Factories;

use App\Models\AttendanceRegister;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRegister>
 */
class AttendanceRegisterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_class_id' => CourseClass::factory(),
            'date' => now()->toDateString(),
        ];
    }
}
