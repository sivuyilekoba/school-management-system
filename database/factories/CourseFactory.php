<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst(is_array($words) ? implode(' ', $words) : $words),
            'short_name' => strtoupper(fake()->lexify('???')),
            'description' => null,
        ];
    }
}
