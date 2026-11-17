<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\CourseClass;
use Illuminate\Database\Seeder;

class DemoAssessmentSeeder extends Seeder
{
    /**
     * Seed demo assessments and marks for local development.
     */
    public function run(): void
    {
        $classes = CourseClass::query()
            ->with('students:id')
            ->orderBy('id')
            ->get()
            ->values();

        foreach ($classes as $classIndex => $courseClass) {
            if ($courseClass->students->isEmpty()) {
                continue;
            }

            $assessments = [
                [
                    'name' => 'Term 1 test',
                    'assessed_on' => now()->toDateString(),
                    'max_score' => 100,
                ],
                [
                    'name' => 'Term 1 assignment',
                    'assessed_on' => now()->subDays(7)->toDateString(),
                    'max_score' => 50,
                ],
            ];

            foreach ($assessments as $assessmentIndex => $data) {
                $assessment = Assessment::query()->firstOrCreate(
                    [
                        'course_class_id' => $courseClass->id,
                        'name' => $data['name'],
                    ],
                    [
                        'assessed_on' => $data['assessed_on'],
                        'max_score' => $data['max_score'],
                    ],
                );

                foreach ($courseClass->students->values() as $studentIndex => $student) {
                    if (($classIndex + $studentIndex + $assessmentIndex) % 7 === 5) {
                        continue;
                    }

                    $percentage = 60 + (($classIndex * 7 + $studentIndex * 11 + $assessmentIndex * 13) % 40);

                    $assessment->marks()->updateOrCreate(
                        ['student_id' => $student->id],
                        ['score' => round($data['max_score'] * $percentage / 100, 2)],
                    );
                }
            }
        }
    }
}
