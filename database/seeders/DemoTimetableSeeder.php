<?php

namespace Database\Seeders;

use App\Models\CourseClass;
use App\Models\TimetablePeriod;
use Illuminate\Database\Seeder;

class DemoTimetableSeeder extends Seeder
{
    /**
     * Seed demo timetable periods and a handful of lessons for local development.
     */
    public function run(): void
    {
        $periods = [
            ['name' => 'Period 1', 'start_time' => '08:00', 'end_time' => '08:45'],
            ['name' => 'Period 2', 'start_time' => '08:50', 'end_time' => '09:35'],
            ['name' => 'Period 3', 'start_time' => '09:55', 'end_time' => '10:40'],
            ['name' => 'Period 4', 'start_time' => '10:45', 'end_time' => '11:30'],
            ['name' => 'Period 5', 'start_time' => '12:10', 'end_time' => '12:55'],
        ];

        foreach ($periods as $period) {
            TimetablePeriod::query()->firstOrCreate(
                ['name' => $period['name']],
                $period,
            );
        }

        $periodIds = TimetablePeriod::query()->pluck('id', 'name');

        $schedule = [
            [
                'course' => 'Mathematics',
                'class' => 'A',
                'room' => 'Room 12',
                'lessons' => [
                    ['day' => 1, 'period' => 'Period 1'],
                    ['day' => 3, 'period' => 'Period 1'],
                    ['day' => 5, 'period' => 'Period 2'],
                ],
            ],
            [
                'course' => 'Mathematics',
                'class' => 'B',
                'room' => 'Room 12',
                'lessons' => [
                    ['day' => 2, 'period' => 'Period 1'],
                    ['day' => 4, 'period' => 'Period 2'],
                ],
            ],
            [
                'course' => 'English',
                'class' => 'A',
                'room' => 'Room 4',
                'lessons' => [
                    ['day' => 1, 'period' => 'Period 2'],
                    ['day' => 4, 'period' => 'Period 1'],
                ],
            ],
            [
                'course' => 'Science',
                'class' => 'A',
                'room' => 'Lab 1',
                'lessons' => [
                    ['day' => 2, 'period' => 'Period 2'],
                    ['day' => 5, 'period' => 'Period 3'],
                ],
            ],
        ];

        foreach ($schedule as $item) {
            $courseClass = CourseClass::query()
                ->where('name', $item['class'])
                ->whereHas('course', fn ($query) => $query->where('name', $item['course']))
                ->first();

            if ($courseClass === null) {
                continue;
            }

            foreach ($item['lessons'] as $lesson) {
                $periodId = $periodIds[$lesson['period']] ?? null;

                if ($periodId === null) {
                    continue;
                }

                $courseClass->timetableEntries()->firstOrCreate(
                    [
                        'day_of_week' => $lesson['day'],
                        'timetable_period_id' => $periodId,
                    ],
                    ['room' => $item['room']],
                );
            }
        }
    }
}
