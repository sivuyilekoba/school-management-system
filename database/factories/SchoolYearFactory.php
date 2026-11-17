<?php

namespace Database\Factories;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolYear>
 */
class SchoolYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2100);

        return [
            'name' => $startYear.'-'.($startYear + 1),
            'start_date' => $startYear.'-09-01',
            'end_date' => ($startYear + 1).'-06-30',
            'is_current' => false,
        ];
    }
}
