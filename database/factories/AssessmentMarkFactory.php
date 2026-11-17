<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentMark;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentMark>
 */
class AssessmentMarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => fake()->numberBetween(40, 100),
        ];
    }
}
