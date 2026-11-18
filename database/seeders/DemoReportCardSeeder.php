<?php

namespace Database\Seeders;

use App\Models\ReportCard;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Seeder;

class DemoReportCardSeeder extends Seeder
{
    /**
     * Seed a draft and a published report card for local development.
     */
    public function run(): void
    {
        $schoolYear = SchoolYear::query()->where('is_current', true)->first();

        if ($schoolYear === null) {
            return;
        }

        $today = now()->toDateString();

        $term = $schoolYear->terms()
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first();

        if (! $term instanceof Term) {
            $term = $schoolYear->terms()->orderBy('start_date')->first();
        }

        if (! $term instanceof Term) {
            return;
        }

        $students = Student::query()->orderBy('id')->get()->values();

        if ($students->isEmpty()) {
            return;
        }

        $draftStudent = $students->get(0);

        if ($draftStudent instanceof Student) {
            ReportCard::query()->updateOrCreate(
                [
                    'student_id' => $draftStudent->id,
                    'term_id' => $term->id,
                ],
                [
                    'comment' => 'Settled into the term well and contributes consistently in class.',
                    'published_at' => null,
                ],
            );
        }

        $publishedStudent = $students->get(1);

        if ($publishedStudent instanceof Student) {
            ReportCard::query()->updateOrCreate(
                [
                    'student_id' => $publishedStudent->id,
                    'term_id' => $term->id,
                ],
                [
                    'comment' => 'A strong term with steady progress across all subjects. Keep up the effort.',
                    'published_at' => now(),
                ],
            );
        }
    }
}
