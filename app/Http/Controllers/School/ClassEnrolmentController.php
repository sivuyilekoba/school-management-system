<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreEnrolmentRequest;
use App\Models\CourseClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassEnrolmentController extends Controller
{
    /**
     * Show the students enrolled in a class.
     */
    public function index(Request $request, CourseClass $courseClass): Response
    {
        $courseClass->loadMissing('course');

        $search = trim((string) $request->query('q', ''));

        $enrolled = $courseClass->students()
            ->with('family:id,name')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Student $student): array => $this->studentData($student))
            ->values();

        $available = Student::query()
            ->whereDoesntHave('enrolments', fn (Builder $query): Builder => $query
                ->where('course_class_id', $courseClass->id))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('surname', 'like', '%'.$search.'%')
                        ->orWhere('preferred_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->with('family:id,name')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->limit(25)
            ->get()
            ->map(fn (Student $student): array => $this->studentData($student))
            ->values();

        return Inertia::render('school/courses/students/Index', [
            'courseClass' => [
                'id' => $courseClass->id,
                'course_id' => $courseClass->course_id,
                'name' => $courseClass->displayName(),
            ],
            'enrolled' => $enrolled,
            'available' => $available,
            'search' => $search,
        ]);
    }

    /**
     * Enrol a student in the class.
     */
    public function store(StoreEnrolmentRequest $request, CourseClass $courseClass): RedirectResponse
    {
        $courseClass->students()->attach($request->validated('student_id'));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Student enrolled.',
        ]);
    }

    /**
     * Remove a student from the class.
     */
    public function destroy(CourseClass $courseClass, Student $student): RedirectResponse
    {
        $courseClass->students()->detach($student->id);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Student removed from the class.',
        ]);
    }

    /**
     * The student data shown on the enrolment page.
     *
     * @return array{id: int, name: string, email: string|null, family: string|null}
     */
    private function studentData(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->displayName(),
            'email' => $student->email,
            'family' => $student->family?->name,
        ];
    }
}
