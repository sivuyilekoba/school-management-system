<?php

namespace App\Concerns;

use App\Enums\AttendanceStatus;
use App\Models\Assessment;
use App\Models\AssessmentMark;
use App\Models\AttendanceRecord;
use App\Models\CourseClass;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ReportCardData
{
    /**
     * Build the full report card payload shared by the page, print view, and PDF.
     *
     * @return array{
     *     term: array{id: int, name: string, start_date: string, end_date: string, school_year: array{id: int, name: string}},
     *     student: array{id: int, name: string, email: string|null, family: string|null},
     *     classes: array<int, array{id: int, name: string, assessments: array<int, array{id: int, name: string, assessed_on: string, score: float|null, max_score: float, percentage: float|null}>, score: float, max_score: float, percentage: float|null}>,
     *     overall: array{percentage: float|null, assessments_count: int, marked_count: int},
     *     attendance: array{present: int, absent: int, late: int, excused: int, total: int},
     *     reportCard: array{comment: string|null, published: bool, published_at: string|null}
     * }
     */
    protected function reportData(Student $student, Term $term): array
    {
        $term->loadMissing('schoolYear');
        $student->loadMissing('family');

        $classes = $student->courseClasses()
            ->with('course:id,name')
            ->get()
            ->sortBy(fn (CourseClass $courseClass): string => $courseClass->displayName())
            ->values();

        $assessments = Assessment::query()
            ->whereIn('course_class_id', $classes->modelKeys())
            ->whereDate('assessed_on', '>=', $term->start_date->toDateString())
            ->whereDate('assessed_on', '<=', $term->end_date->toDateString())
            ->with(['marks' => fn (HasMany $query): HasMany => $query->where('student_id', $student->id)])
            ->orderBy('assessed_on')
            ->orderBy('name')
            ->get()
            ->groupBy('course_class_id');

        $classRows = $classes
            ->map(function (CourseClass $courseClass) use ($assessments): array {
                $classAssessments = $assessments->get($courseClass->id);

                return $this->classReport(
                    $courseClass,
                    $classAssessments instanceof EloquentCollection
                        ? $classAssessments
                        : new EloquentCollection,
                );
            })
            ->values();

        $overallScore = 0.0;
        $overallMaxScore = 0.0;
        $assessmentsCount = 0;
        $markedCount = 0;

        foreach ($classRows as $classRow) {
            $assessmentsCount += count($classRow['assessments']);
            $markedCount += count(array_filter(
                $classRow['assessments'],
                fn (array $assessment): bool => $assessment['score'] !== null,
            ));

            if ($classRow['percentage'] === null) {
                continue;
            }

            $overallScore += $classRow['score'];
            $overallMaxScore += $classRow['max_score'];
        }

        $reportCard = ReportCard::query()
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        return [
            'term' => $this->termData($term),
            'student' => [
                'id' => $student->id,
                'name' => $student->displayName(),
                'email' => $student->email,
                'family' => $student->family?->name,
            ],
            'classes' => $classRows->all(),
            'overall' => [
                'percentage' => $overallMaxScore > 0
                    ? round($overallScore / $overallMaxScore * 100, 1)
                    : null,
                'assessments_count' => $assessmentsCount,
                'marked_count' => $markedCount,
            ],
            'attendance' => $this->attendanceFor($student, $term),
            'reportCard' => [
                'comment' => $reportCard?->comment,
                'published' => $reportCard?->isPublished() ?? false,
                'published_at' => $reportCard?->published_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Build the assessment summary for one class.
     *
     * @param  EloquentCollection<int, Assessment>  $assessments
     * @return array{
     *     id: int,
     *     name: string,
     *     assessments: array<int, array{id: int, name: string, assessed_on: string, score: float|null, max_score: float, percentage: float|null}>,
     *     score: float,
     *     max_score: float,
     *     percentage: float|null
     * }
     */
    protected function classReport(CourseClass $courseClass, EloquentCollection $assessments): array
    {
        $rows = $assessments
            ->map(function (Assessment $assessment): array {
                $mark = $assessment->marks->first();
                $score = $mark instanceof AssessmentMark ? (float) $mark->score : null;
                $maxScore = (float) $assessment->max_score;

                return [
                    'id' => $assessment->id,
                    'name' => $assessment->name,
                    'assessed_on' => $assessment->assessed_on->toDateString(),
                    'score' => $score,
                    'max_score' => $maxScore,
                    'percentage' => $score !== null && $maxScore > 0
                        ? round($score / $maxScore * 100, 1)
                        : null,
                ];
            })
            ->values();

        $scored = $rows->filter(fn (array $assessment): bool => $assessment['score'] !== null);

        $score = (float) $scored->sum(fn (array $assessment): float => (float) $assessment['score']);
        $maxScore = (float) $scored->sum(fn (array $assessment): float => (float) $assessment['max_score']);

        return [
            'id' => $courseClass->id,
            'name' => $courseClass->displayName(),
            'assessments' => $rows->all(),
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $maxScore > 0 ? round($score / $maxScore * 100, 1) : null,
        ];
    }

    /**
     * Count a student's attendance within a term.
     *
     * @return array{present: int, absent: int, late: int, excused: int, total: int}
     */
    protected function attendanceFor(Student $student, Term $term): array
    {
        $counts = $this->emptyAttendance();

        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('register', fn (Builder $query): Builder => $query
                ->whereDate('date', '>=', $term->start_date->toDateString())
                ->whereDate('date', '<=', $term->end_date->toDateString()))
            ->get(['id', 'student_id', 'status']);

        foreach ($records as $record) {
            $counts[$record->status->value]++;
        }

        $counts['total'] = $counts['present'] + $counts['absent'] + $counts['late'] + $counts['excused'];

        return $counts;
    }

    /**
     * The empty attendance counters.
     *
     * @return array{present: int, absent: int, late: int, excused: int, total: int}
     */
    protected function emptyAttendance(): array
    {
        return [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Absent->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::Excused->value => 0,
            'total' => 0,
        ];
    }

    /**
     * The term data shown on report card pages.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     start_date: string,
     *     end_date: string,
     *     school_year: array{id: int, name: string}
     * }
     */
    protected function termData(Term $term): array
    {
        return [
            'id' => $term->id,
            'name' => $term->name,
            'start_date' => $term->start_date->toDateString(),
            'end_date' => $term->end_date->toDateString(),
            'school_year' => [
                'id' => $term->schoolYear->id,
                'name' => $term->schoolYear->name,
            ],
        ];
    }
}
