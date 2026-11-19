<?php

namespace App\Http\Controllers\Portal;

use App\Concerns\PortalAccess;
use App\Concerns\ReportCardData;
use App\Http\Controllers\Controller;
use App\Models\ReportCard;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class ReportCardController extends Controller
{
    use PortalAccess, ReportCardData;

    /**
     * List the published report cards for the portal user's students.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $students = $this->portalStudents($user);

        $reportCards = ReportCard::query()
            ->whereIn('student_id', $students->modelKeys())
            ->whereNotNull('published_at')
            ->with([
                'student:id,first_name,surname,preferred_name',
                'term:id,school_year_id,name,start_date,end_date',
                'term.schoolYear:id,name',
            ])
            ->get()
            ->sortByDesc(fn (ReportCard $reportCard): string => $reportCard->term->start_date->toDateString())
            ->values()
            ->map(fn (ReportCard $reportCard): array => [
                'id' => $reportCard->id,
                'student' => [
                    'id' => $reportCard->student->id,
                    'name' => $reportCard->student->displayName(),
                ],
                'term' => [
                    'id' => $reportCard->term->id,
                    'name' => $reportCard->term->name,
                    'start_date' => $reportCard->term->start_date->toDateString(),
                    'end_date' => $reportCard->term->end_date->toDateString(),
                    'school_year' => $reportCard->term->schoolYear->name,
                ],
                'published_at' => $reportCard->published_at?->format('Y-m-d'),
            ]);

        return Inertia::render('portal/reports/Index', [
            'students' => $students
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $student->displayName(),
                ])
                ->values(),
            'reportCards' => $reportCards,
        ]);
    }

    /**
     * Show a published report card to the portal user.
     */
    public function show(Request $request, Student $student, Term $term): Response
    {
        $this->authorise($request, $student, $term);

        return Inertia::render('portal/reports/Show', [
            ...$this->reportData($student, $term),
            'backUrl' => route('portal.reports.index'),
        ]);
    }

    /**
     * Show a printer-friendly published report card.
     */
    public function print(Request $request, Student $student, Term $term): ViewContract
    {
        $this->authorise($request, $student, $term);

        return view('school.reports.print', [
            ...$this->reportData($student, $term),
            'school' => [
                'name' => Setting::get('school.name', config('app.name')),
            ],
            'forPdf' => false,
            'backUrl' => route('portal.reports.show', ['student' => $student, 'term' => $term]),
        ]);
    }

    /**
     * Download a published report card as a PDF.
     */
    public function pdf(Request $request, Student $student, Term $term): PdfBuilder
    {
        $this->authorise($request, $student, $term);

        return Pdf::view('school.reports.print', [
            ...$this->reportData($student, $term),
            'school' => [
                'name' => Setting::get('school.name', config('app.name')),
            ],
            'forPdf' => true,
            'backUrl' => route('portal.reports.show', ['student' => $student, 'term' => $term]),
        ])
            ->format('a4')
            ->download(Str::slug($student->displayName().' '.$term->name.' report card').'.pdf');
    }

    /**
     * Ensure the student belongs to the portal user and the card is published.
     */
    private function authorise(Request $request, Student $student, Term $term): void
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $this->ensureStudentAccess($user, $student);

        abort_unless(
            ReportCard::query()
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->whereNotNull('published_at')
                ->exists(),
            404,
        );
    }
}
