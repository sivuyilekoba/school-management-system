<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreTimetablePeriodRequest;
use App\Http\Requests\School\UpdateTimetablePeriodRequest;
use App\Models\TimetablePeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TimetablePeriodController extends Controller
{
    /**
     * List the school's timetable periods.
     */
    public function index(): Response
    {
        $periods = TimetablePeriod::query()
            ->withCount('entries')
            ->orderBy('start_time')
            ->get()
            ->map(fn (TimetablePeriod $period): array => [
                'id' => $period->id,
                'name' => $period->name,
                'start_time' => $period->start_time,
                'end_time' => $period->end_time,
                'entries_count' => $period->entries_count,
            ]);

        return Inertia::render('school/timetable/periods/Index', [
            'periods' => $periods,
        ]);
    }

    /**
     * Show the form for creating a timetable period.
     */
    public function create(): Response
    {
        return Inertia::render('school/timetable/periods/Create');
    }

    /**
     * Store a newly created timetable period.
     */
    public function store(StoreTimetablePeriodRequest $request): RedirectResponse
    {
        TimetablePeriod::query()->create($request->validated());

        return to_route('school.timetable.periods.index')->with('toast', [
            'type' => 'success',
            'message' => 'Period created.',
        ]);
    }

    /**
     * Show the form for editing a timetable period.
     */
    public function edit(TimetablePeriod $period): Response
    {
        return Inertia::render('school/timetable/periods/Edit', [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_time' => $period->start_time,
                'end_time' => $period->end_time,
            ],
        ]);
    }

    /**
     * Update the given timetable period.
     */
    public function update(UpdateTimetablePeriodRequest $request, TimetablePeriod $period): RedirectResponse
    {
        $period->update($request->validated());

        return to_route('school.timetable.periods.index')->with('toast', [
            'type' => 'success',
            'message' => 'Period updated.',
        ]);
    }
}
