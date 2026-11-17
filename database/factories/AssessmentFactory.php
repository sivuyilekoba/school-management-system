<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
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
            'name' => fake()->words(3, true),
            'assessed_on' => now()->toDateString(),
            'max_score' => 100,
        ];
    }
}
