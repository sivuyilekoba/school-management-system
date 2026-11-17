<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_year_id' => SchoolYear::factory(),
            'name' => 'Term '.fake()->numberBetween(1, 4),
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
        ];
    }
}
