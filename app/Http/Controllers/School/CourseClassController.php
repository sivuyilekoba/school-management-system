<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreCourseClassRequest;
use App\Http\Requests\School\UpdateCourseClassRequest;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CourseClassController extends Controller
{
    /**
     * Show the form for creating a class in the given course.
     */
    public function create(Course $course): Response
    {
        return Inertia::render('school/courses/classes/Create', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
            ],
            'staff' => $this->staffOptions(),
        ]);
    }

    /**
     * Store a newly created class in the given course.
     */
    public function store(StoreCourseClassRequest $request, Course $course): RedirectResponse
    {
        $course->classes()->create($request->validated());

        return to_route('school.courses.edit', $course)->with('toast', [
            'type' => 'success',
            'message' => 'Class created.',
        ]);
    }

    /**
     * Show the form for editing a class.
     */
    public function edit(CourseClass $courseClass): Response
    {
        return Inertia::render('school/courses/classes/Edit', [
            'courseClass' => [
                'id' => $courseClass->id,
                'name' => $courseClass->name,
                'staff_id' => $courseClass->staff_id,
            ],
            'course' => [
                'id' => $courseClass->course_id,
                'name' => $courseClass->course->name,
            ],
            'staff' => $this->staffOptions(),
        ]);
    }

    /**
     * Update the given class.
     */
    public function update(UpdateCourseClassRequest $request, CourseClass $courseClass): RedirectResponse
    {
        $courseClass->update($request->validated());

        return to_route('school.courses.edit', $courseClass->course_id)->with('toast', [
            'type' => 'success',
            'message' => 'Class updated.',
        ]);
    }

    /**
     * The staff members a class can be assigned to.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    private function staffOptions(): Collection
    {
        return Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'preferred_name'])
            ->map(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->displayName(),
            ])
            ->values();
    }
}
