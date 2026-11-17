<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreSchoolYearRequest;
use App\Http\Requests\School\UpdateSchoolYearRequest;
use App\Models\SchoolYear;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    /**
     * List the school years and their terms.
     */
    public function index(): Response
    {
        $years = SchoolYear::query()
            ->withCount('terms')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (SchoolYear $year): array => [
                'id' => $year->id,
                'name' => $year->name,
                'start_date' => $year->start_date->toDateString(),
                'end_date' => $year->end_date->toDateString(),
                'is_current' => $year->is_current,
                'terms_count' => $year->terms_count,
            ]);

        return Inertia::render('school/calendar/Index', [
            'years' => $years,
        ]);
    }

    /**
     * Show the form for creating a school year.
     */
    public function create(): Response
    {
        return Inertia::render('school/calendar/Create');
    }

    /**
     * Store a newly created school year.
     */
    public function store(StoreSchoolYearRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();

            if ((bool) ($data['is_current'] ?? false)) {
                SchoolYear::query()->where('is_current', true)->update(['is_current' => false]);
            }

            SchoolYear::query()->create($data);
        });

        return to_route('school.calendar.index')->with('toast', [
            'type' => 'success',
            'message' => 'School year created.',
        ]);
    }

    /**
     * Show the form for editing a school year.
     */
    public function edit(SchoolYear $schoolYear): Response
    {
        return Inertia::render('school/calendar/Edit', [
            'year' => [
                'id' => $schoolYear->id,
                'name' => $schoolYear->name,
                'start_date' => $schoolYear->start_date->toDateString(),
                'end_date' => $schoolYear->end_date->toDateString(),
                'is_current' => $schoolYear->is_current,
            ],
            'terms' => $schoolYear->terms()
                ->orderBy('start_date')
                ->get()
                ->map(fn (Term $term): array => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'start_date' => $term->start_date->toDateString(),
                    'end_date' => $term->end_date->toDateString(),
                ])
                ->all(),
        ]);
    }

    /**
     * Update the given school year.
     */
    public function update(UpdateSchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        DB::transaction(function () use ($request, $schoolYear): void {
            $data = $request->validated();

            if ((bool) ($data['is_current'] ?? false)) {
                SchoolYear::query()
                    ->whereKeyNot($schoolYear->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $schoolYear->update($data);
        });

        return to_route('school.calendar.index')->with('toast', [
            'type' => 'success',
            'message' => 'School year updated.',
        ]);
    }
}
