<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\SchoolYear;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class DemoCurriculumSeeder extends Seeder
{
    /**
     * Seed a demo school year, terms, courses and classes for local development.
     */
    public function run(): void
    {
        $schoolYear = SchoolYear::query()->firstOrCreate(
            ['name' => '2026-2027'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_current' => true,
            ],
        );

        SchoolYear::query()
            ->whereKeyNot($schoolYear->id)
            ->update(['is_current' => false]);

        $terms = [
            ['name' => 'Term 1', 'start_date' => '2026-09-01', 'end_date' => '2026-12-04'],
            ['name' => 'Term 2', 'start_date' => '2027-01-12', 'end_date' => '2027-03-26'],
            ['name' => 'Term 3', 'start_date' => '2027-04-12', 'end_date' => '2027-06-30'],
        ];

        foreach ($terms as $term) {
            $schoolYear->terms()->firstOrCreate(
                ['name' => $term['name']],
                ['start_date' => $term['start_date'], 'end_date' => $term['end_date']],
            );
        }

        $staff = Staff::query()->orderBy('id')->get();

        $courses = [
            ['name' => 'Mathematics', 'short_name' => 'MATH', 'classes' => ['A', 'B']],
            ['name' => 'English', 'short_name' => 'ENG', 'classes' => ['A']],
            ['name' => 'Science', 'short_name' => 'SCI', 'classes' => ['A', 'B']],
            ['name' => 'History', 'short_name' => 'HIST', 'classes' => ['A']],
        ];

        $classIndex = 0;

        foreach ($courses as $courseData) {
            $course = Course::query()->firstOrCreate(
                ['name' => $courseData['name']],
                ['short_name' => $courseData['short_name']],
            );

            foreach ($courseData['classes'] as $className) {
                $teacher = $staff->isEmpty()
                    ? null
                    : $staff->get($classIndex % $staff->count());

                $course->classes()->firstOrCreate(
                    ['name' => $className],
                    ['staff_id' => $teacher?->id],
                );

                $classIndex++;
            }
        }
    }
}
