<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SaveAssessmentMarksRequest;
use App\Http\Requests\School\StoreAssessmentRequest;
use App\Http\Requests\School\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Models\CourseClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    /**
     * List the classes that assessments can be recorded for.
     */
    public function index(): Response
    {
        $classes = CourseClass::query()
            ->with(['course:id,name', 'staff:id,first_name,surname,preferred_name'])
            ->withCount(['assessments', 'students'])
            ->get()
            ->sortBy(fn (CourseClass $courseClass): string => $courseClass->displayName())
            ->values()
            ->map(fn (CourseClass $courseClass): array => [
                'id' => $courseClass->id,
                'name' => $courseClass->displayName(),
                'teacher' => $courseClass->staff?->displayName(),
                'students_count' => $courseClass->students_count,
                'assessments_count' => $courseClass->assessments_count,
            ])
            ->values();

        return Inertia::render('school/assessment/Index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Show the assessments set for a class.
     */
    public function classIndex(CourseClass $courseClass): Response
    {
        $courseClass->loadMissing(['course', 'staff']);

        $assessments = $courseClass->assessments()
            ->withCount('marks')
            ->orderByDesc('assessed_on')
            ->orderBy('name')
            ->get()
            ->map(fn (Assessment $assessment): array => [
                'id' => $assessment->id,
                'name' => $assessment->name,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'max_score' => (float) $assessment->max_score,
                'marks_count' => $assessment->marks_count,
            ])
            ->values();

        return Inertia::render('school/assessment/classes/Index', [
            'courseClass' => $this->courseClassData($courseClass),
            'assessments' => $assessments,
            'students_count' => $courseClass->students()->count(),
        ]);
    }

    /**
     * Show the form for creating an assessment.
     */
    public function create(CourseClass $courseClass): Response
    {
        $courseClass->loadMissing('course');

        return Inertia::render('school/assessment/assessments/Create', [
            'courseClass' => $this->courseClassData($courseClass),
            'defaults' => [
                'assessed_on' => now()->toDateString(),
            ],
        ]);
    }

    /**
     * Store a newly created assessment.
     */
    public function store(StoreAssessmentRequest $request, CourseClass $courseClass): RedirectResponse
    {
        $courseClass->assessments()->create($request->validated());

        return to_route('school.assessment.classes.index', $courseClass)->with('toast', [
            'type' => 'success',
            'message' => 'Assessment created.',
        ]);
    }

    /**
     * Show the form for editing an assessment.
     */
    public function edit(Assessment $assessment): Response
    {
        $assessment->loadMissing('courseClass.course');

        return Inertia::render('school/assessment/assessments/Edit', [
            'courseClass' => $this->courseClassData($assessment->courseClass),
            'assessment' => [
                'id' => $assessment->id,
                'name' => $assessment->name,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'max_score' => (float) $assessment->max_score,
            ],
        ]);
    }

    /**
     * Update the given assessment.
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $assessment->update($request->validated());

        return to_route('school.assessment.classes.index', $assessment->course_class_id)->with('toast', [
            'type' => 'success',
            'message' => 'Assessment updated.',
        ]);
    }

    /**
     * Remove the given assessment.
     */
    public function destroy(Assessment $assessment): RedirectResponse
    {
        $courseClassId = $assessment->course_class_id;

        $assessment->delete();

        return to_route('school.assessment.classes.index', $courseClassId)->with('toast', [
            'type' => 'success',
            'message' => 'Assessment removed.',
        ]);
    }

    /**
     * Show the class roster for entering assessment marks.
     */
    public function marks(Assessment $assessment): Response
    {
        $assessment->loadMissing(['courseClass.course', 'courseClass.staff']);

        $scores = $assessment->marks()->pluck('score', 'student_id');

        $students = $assessment->courseClass
            ->students()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $student) use ($scores): array {
                $score = $scores->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->displayName(),
                    'score' => is_string($score) ? $score : '',
                ];
            })
            ->values();

        return Inertia::render('school/assessment/assessments/Marks', [
            'courseClass' => $this->courseClassData($assessment->courseClass),
            'assessment' => $this->assessmentData($assessment),
            'students' => $students,
        ]);
    }

    /**
     * Save the marks entered for an assessment.
     */
    public function saveMarks(SaveAssessmentMarksRequest $request, Assessment $assessment): RedirectResponse
    {
        $records = $request->markRecords();

        DB::transaction(function () use ($assessment, $records): void {
            $studentIds = $assessment->courseClass->students()->pluck('students.id')->all();

            $assessment->marks()
                ->whereNotIn('student_id', $studentIds)
                ->delete();

            foreach ($records as $record) {
                if ($record['score'] === null) {
                    $assessment->marks()
                        ->where('student_id', $record['student_id'])
                        ->delete();

                    continue;
                }

                $assessment->marks()->updateOrCreate(
                    ['student_id' => $record['student_id']],
                    ['score' => $record['score']],
                );
            }
        });

        return to_route('school.assessment.marks', $assessment)->with('toast', [
            'type' => 'success',
            'message' => 'Marks saved.',
        ]);
    }

    /**
     * The class data shown on assessment pages.
     *
     * @return array{id: int, name: string}
     */
    private function courseClassData(CourseClass $courseClass): array
    {
        return [
            'id' => $courseClass->id,
            'name' => $courseClass->displayName(),
        ];
    }

    /**
     * The assessment data shown on marks pages.
     *
     * @return array{id: int, name: string, assessed_on: string, max_score: float}
     */
    private function assessmentData(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'name' => $assessment->name,
            'assessed_on' => $assessment->assessed_on->toDateString(),
            'max_score' => (float) $assessment->max_score,
        ];
    }
}
