<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreCourseRequest;
use App\Http\Requests\School\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * List the school's courses.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->withCount('classes')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('short_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'short_name' => $course->short_name,
                'classes_count' => $course->classes_count,
            ]);

        return Inertia::render('school/courses/Index', [
            'courses' => $courses,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a course.
     */
    public function create(): Response
    {
        return Inertia::render('school/courses/Create');
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::query()->create($request->validated());

        return to_route('school.courses.index')->with('toast', [
            'type' => 'success',
            'message' => 'Course created.',
        ]);
    }

    /**
     * Show the form for editing a course.
     */
    public function edit(Course $course): Response
    {
        return Inertia::render('school/courses/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'short_name' => $course->short_name,
                'description' => $course->description,
            ],
            'classes' => $course->classes()
                ->with('staff:id,first_name,surname,preferred_name')
                ->withCount('students')
                ->orderBy('name')
                ->get()
                ->map(fn (CourseClass $class): array => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'teacher' => $class->staff?->displayName(),
                    'students_count' => $class->students_count,
                ])
                ->all(),
        ]);
    }

    /**
     * Update the given course.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return to_route('school.courses.index')->with('toast', [
            'type' => 'success',
            'message' => 'Course updated.',
        ]);
    }
}
