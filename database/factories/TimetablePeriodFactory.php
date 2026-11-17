<?php

namespace Database\Factories;

use App\Models\TimetablePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetablePeriod>
 */
class TimetablePeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Period '.fake()->unique()->numberBetween(1, 20),
            'start_time' => '08:00',
            'end_time' => '08:45',
        ];
    }
}
