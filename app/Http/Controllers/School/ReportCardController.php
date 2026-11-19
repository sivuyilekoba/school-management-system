<?php

namespace App\Http\Controllers\School;

use App\Concerns\ReportCardData;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\SaveReportCardRequest;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
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
    use ReportCardData;

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
            'backUrl' => route('school.reports.show', ['student' => $student, 'term' => $term]),
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
            'backUrl' => route('school.reports.show', ['student' => $student, 'term' => $term]),
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
}
