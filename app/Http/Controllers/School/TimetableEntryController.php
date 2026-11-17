<?php

namespace App\Http\Controllers\School;

use App\Concerns\TimetableOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreTimetableEntryRequest;
use App\Http\Requests\School\UpdateTimetableEntryRequest;
use App\Models\CourseClass;
use App\Models\TimetableEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetableEntryController extends Controller
{
    use TimetableOptions;

    /**
     * Show the form for adding a lesson to a class timetable.
     */
    public function create(Request $request, CourseClass $courseClass): Response
    {
        $courseClass->loadMissing('course');

        $day = $request->integer('day');

        return Inertia::render('school/timetable/entries/Create', [
            'courseClass' => $this->courseClassData($courseClass),
            'periods' => $this->periodOptions(),
            'days' => $this->dayOptions(),
            'defaults' => [
                'day_of_week' => $day >= 1 && $day <= 5 ? $day : null,
                'timetable_period_id' => $request->integer('period') ?: null,
            ],
        ]);
    }

    /**
     * Store a newly created lesson in the class timetable.
     */
    public function store(StoreTimetableEntryRequest $request, CourseClass $courseClass): RedirectResponse
    {
        $courseClass->timetableEntries()->create($request->validated());

        return to_route('school.timetable.index', ['class' => $courseClass->id])->with('toast', [
            'type' => 'success',
            'message' => 'Lesson added to the timetable.',
        ]);
    }

    /**
     * Show the form for editing a timetable lesson.
     */
    public function edit(TimetableEntry $entry): Response
    {
        $entry->loadMissing('courseClass.course');

        return Inertia::render('school/timetable/entries/Edit', [
            'entry' => [
                'id' => $entry->id,
                'day_of_week' => $entry->day_of_week,
                'timetable_period_id' => $entry->timetable_period_id,
                'room' => $entry->room,
            ],
            'courseClass' => $this->courseClassData($entry->courseClass),
            'periods' => $this->periodOptions(),
            'days' => $this->dayOptions(),
        ]);
    }

    /**
     * Update the given timetable lesson.
     */
    public function update(UpdateTimetableEntryRequest $request, TimetableEntry $entry): RedirectResponse
    {
        $entry->update($request->validated());

        return to_route('school.timetable.index', ['class' => $entry->course_class_id])->with('toast', [
            'type' => 'success',
            'message' => 'Lesson updated.',
        ]);
    }

    /**
     * Remove the given lesson from the timetable.
     */
    public function destroy(TimetableEntry $entry): RedirectResponse
    {
        $courseClassId = $entry->course_class_id;

        $entry->delete();

        return to_route('school.timetable.index', ['class' => $courseClassId])->with('toast', [
            'type' => 'success',
            'message' => 'Lesson removed.',
        ]);
    }

    /**
     * The class data shown on timetable forms.
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
}
