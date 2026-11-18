<?php

namespace Database\Factories;

use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCard>
 */
class ReportCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'term_id' => Term::factory(),
            'comment' => fake()->sentence(),
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the report card has been published.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now(),
        ]);
    }
}
