<?php

namespace App\Http\Controllers\School;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\SaveReportCardRequest;
use App\Models\Assessment;
use App\Models\AssessmentMark;
use App\Models\AttendanceRecord;
use App\Models\CourseClass;
use App\Models\ReportCard;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Term;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class ReportCardController extends Controller
{
    /**
     * List the students with a report card for a term.
     */
    public function index(Request $request): Response
    {
        $terms = Term::query()
            ->with('schoolYear:id,name,start_date,is_current')
            ->orderBy('start_date')
            ->get();

        $term = $this->resolveTerm($request->query('term'), $terms);

        if (! $term instanceof Term) {
            return Inertia::render('school/reports/Index', [
                'schoolYears' => [],
                'term' => null,
                'students' => [],
            ]);
        }

        $students = Student::query()
            ->whereHas('enrolments')
            ->with(['family:id,name'])
            ->withCount('enrolments')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        $metrics = $this->termMetrics($term, $students);

        $reportCards = ReportCard::query()
            ->where('term_id', $term->id)
            ->whereIn('student_id', $students->modelKeys())
            ->get()
            ->keyBy('student_id');

        return Inertia::render('school/reports/Index', [
            'schoolYears' => $this->schoolYearOptions($terms),
            'term' => $this->termData($term),
            'students' => $students
                ->map(function (Student $student) use ($metrics, $reportCards): array {
                    $metric = $metrics->get($student->id);
                    $reportCard = $reportCards->get($student->id);

                    return [
                        'id' => $student->id,
                        'name' => $student->displayName(),
                        'family' => $student->family?->name,
                        'classes_count' => $student->enrolments_count,
                        'average' => is_array($metric) ? $metric['average'] : null,
                        'attendance' => is_array($metric)
                            ? $metric['attendance']
                            : $this->emptyAttendance(),
                        'status' => match (true) {
                            $reportCard instanceof ReportCard && $reportCard->isPublished() => 'published',
                            $reportCard instanceof ReportCard => 'draft',
                            default => 'not_started',
                        },
                    ];
                })
                ->values(),
        ]);
    }

    /**
     * Show a student's report card for a term.
     */
    public function show(Student $student, Term $term): Response
    {
        return Inertia::render('school/reports/Show', $this->reportData($student, $term));
    }

    /**
     * Show a printer-friendly version of a student's report card.
     */
    public function print(Student $student, Term $term): ViewContract
    {
        return view('school.reports.print', [
            ...$this->reportData($student, $term),
            'school' => [
                'name' => Setting::get('school.name', config('app.name')),
            ],
            'forPdf' => false,
        ]);
    }

    /**
     * Download a student's report card as a PDF.
     */
    public function pdf(Student $student, Term $term): PdfBuilder
    {
        return Pdf::view('school.reports.print', [
            ...$this->reportData($student, $term),
            'school' => [
                'name' => Setting::get('school.name', config('app.name')),
            ],
            'forPdf' => true,
        ])
            ->format('a4')
            ->download(Str::slug($student->displayName().' '.$term->name.' report card').'.pdf');
    }

    /**
     * Save the comment on a student's report card.
     */
    public function saveComment(SaveReportCardRequest $request, Student $student, Term $term): RedirectResponse
    {
        $comment = $request->validated('comment');

        ReportCard::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
            ],
            [
                'comment' => is_string($comment) ? $comment : null,
            ],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Report card comment saved.',
        ]);
    }

    /**
     * Publish a student's report card.
     */
    public function publish(Student $student, Term $term): RedirectResponse
    {
        ReportCard::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'term_id' => $term->id,
            ],
            [
                'published_at' => now(),
            ],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Report card published.',
        ]);
    }

    /**
     * Return a published report card to draft.
     */
    public function unpublish(Student $student, Term $term): RedirectResponse
    {
        ReportCard::query()
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->update(['published_at' => null]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Report card unpublished.',
        ]);
    }

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
    private function reportData(Student $student, Term $term): array
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
    private function classReport(CourseClass $courseClass, EloquentCollection $assessments): array
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
        $maxScore = (float) $scored->sum(fn (array $assessment): float => $assessment['max_score']);

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
     * Calculate term averages and attendance counts for the given students.
     *
     * @param  EloquentCollection<int, Student>  $students
     * @return Collection<int, array{average: float|null, attendance: array{present: int, absent: int, late: int, excused: int, total: int}}>
     */
    private function termMetrics(Term $term, EloquentCollection $students): Collection
    {
        $studentIds = $students->modelKeys();
        $scores = [];

        $assessments = Assessment::query()
            ->whereDate('assessed_on', '>=', $term->start_date->toDateString())
            ->whereDate('assessed_on', '<=', $term->end_date->toDateString())
            ->with(['marks' => fn (HasMany $query): HasMany => $query->whereIn('student_id', $studentIds)])
            ->get();

        foreach ($assessments as $assessment) {
            foreach ($assessment->marks as $mark) {
                $totals = $scores[$mark->student_id] ?? ['score' => 0.0, 'max' => 0.0];

                $totals['score'] += (float) $mark->score;
                $totals['max'] += (float) $assessment->max_score;

                $scores[$mark->student_id] = $totals;
            }
        }

        $attendance = [];

        $records = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('register', fn (Builder $query): Builder => $query
                ->whereDate('date', '>=', $term->start_date->toDateString())
                ->whereDate('date', '<=', $term->end_date->toDateString()))
            ->get(['id', 'student_id', 'status']);

        foreach ($records as $record) {
            $attendance[$record->student_id][$record->status->value]
                = ($attendance[$record->student_id][$record->status->value] ?? 0) + 1;
        }

        return $students->mapWithKeys(function (Student $student) use ($scores, $attendance): array {
            $totals = $scores[$student->id] ?? null;
            $counts = $this->emptyAttendance();

            foreach ($attendance[$student->id] ?? [] as $status => $count) {
                if (array_key_exists($status, $counts)) {
                    $counts[$status] = $count;
                }
            }

            $counts['total'] = $counts['present'] + $counts['absent'] + $counts['late'] + $counts['excused'];

            return [
                $student->id => [
                    'average' => is_array($totals) && $totals['max'] > 0
                        ? round($totals['score'] / $totals['max'] * 100, 1)
                        : null,
                    'attendance' => $counts,
                ],
            ];
        });
    }

    /**
     * Count a student's attendance within a term.
     *
     * @return array{present: int, absent: int, late: int, excused: int, total: int}
     */
    private function attendanceFor(Student $student, Term $term): array
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
    private function emptyAttendance(): array
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
     * Choose the requested term, or the term containing today.
     *
     * @param  EloquentCollection<int, Term>  $terms
     */
    private function resolveTerm(mixed $value, EloquentCollection $terms): ?Term
    {
        if (is_string($value) && ctype_digit($value)) {
            $selected = $terms->firstWhere('id', (int) $value);

            if ($selected instanceof Term) {
                return $selected;
            }
        }

        $today = CarbonImmutable::now()->toDateString();

        foreach ($terms as $term) {
            if ($term->start_date->toDateString() <= $today && $term->end_date->toDateString() >= $today) {
                return $term;
            }
        }

        $first = $terms->first();

        return $first instanceof Term ? $first : null;
    }

    /**
     * Group the terms by school year for the term picker.
     *
     * @param  EloquentCollection<int, Term>  $terms
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     start_date: string,
     *     is_current: bool,
     *     terms: array<int, array{id: int, name: string, start_date: string, end_date: string}>
     * }>
     */
    private function schoolYearOptions(EloquentCollection $terms): array
    {
        /** @var array<int, array{id: int, name: string, start_date: string, is_current: bool, terms: array<int, array{id: int, name: string, start_date: string, end_date: string}>}> $years */
        $years = [];

        foreach ($terms as $term) {
            $schoolYear = $term->schoolYear;

            if (! isset($years[$schoolYear->id])) {
                $years[$schoolYear->id] = [
                    'id' => $schoolYear->id,
                    'name' => $schoolYear->name,
                    'start_date' => $schoolYear->start_date->toDateString(),
                    'is_current' => $schoolYear->is_current,
                    'terms' => [],
                ];
            }

            $years[$schoolYear->id]['terms'][] = [
                'id' => $term->id,
                'name' => $term->name,
                'start_date' => $term->start_date->toDateString(),
                'end_date' => $term->end_date->toDateString(),
            ];
        }

        usort($years, fn (array $first, array $second): int => strcmp($second['start_date'], $first['start_date']));

        return $years;
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
    private function termData(Term $term): array
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
