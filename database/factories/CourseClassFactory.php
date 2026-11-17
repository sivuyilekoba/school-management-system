<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseClass>
 */
class CourseClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'staff_id' => null,
            'name' => fake()->randomElement(['A', 'B', 'C']).fake()->numberBetween(1, 12),
        ];
    }
}
