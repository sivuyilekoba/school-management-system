<?php

namespace App\Http\Controllers\School;

use App\Concerns\TimetableOptions;
use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    use TimetableOptions;

    /**
     * Show the weekly timetable for a class.
     */
    public function index(Request $request): Response
    {
        $classes = CourseClass::query()
            ->with(['course:id,name', 'staff:id,first_name,surname,preferred_name'])
            ->get()
            ->sortBy(fn (CourseClass $class): string => $class->displayName())
            ->values()
            ->map(fn (CourseClass $class): array => [
                'id' => $class->id,
                'name' => $class->displayName(),
                'teacher' => $class->staff?->displayName(),
            ])
            ->values();

        $selectedClass = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();

        $entries = $selectedClass === null
            ? collect()
            : TimetableEntry::query()
                ->where('course_class_id', $selectedClass['id'])
                ->get(['id', 'day_of_week', 'timetable_period_id', 'room'])
                ->map(fn (TimetableEntry $entry): array => [
                    'id' => $entry->id,
                    'day_of_week' => $entry->day_of_week,
                    'timetable_period_id' => $entry->timetable_period_id,
                    'room' => $entry->room,
                ])
                ->values();

        return Inertia::render('school/timetable/Index', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'periods' => $this->periodOptions(),
            'entries' => $entries,
            'days' => $this->dayOptions(),
        ]);
    }
}
