<?php

namespace Database\Factories;

use App\Models\CourseClass;
use App\Models\Enrolment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrolment>
 */
class EnrolmentFactory extends Factory
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
            'student_id' => Student::factory(),
        ];
    }
}
