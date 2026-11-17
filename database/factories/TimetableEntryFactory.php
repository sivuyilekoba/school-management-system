<?php

namespace Database\Factories;

use App\Models\CourseClass;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetableEntry>
 */
class TimetableEntryFactory extends Factory
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
            'timetable_period_id' => TimetablePeriod::factory(),
            'day_of_week' => 1,
            'room' => null,
        ];
    }
}
