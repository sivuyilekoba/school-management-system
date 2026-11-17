<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreTermRequest;
use App\Http\Requests\School\UpdateTermRequest;
use App\Models\SchoolYear;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TermController extends Controller
{
    /**
     * Show the form for creating a term in the given school year.
     */
    public function create(SchoolYear $schoolYear): Response
    {
        return Inertia::render('school/calendar/terms/Create', [
            'schoolYear' => $this->schoolYearData($schoolYear),
        ]);
    }

    /**
     * Store a newly created term in the given school year.
     */
    public function store(StoreTermRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $schoolYear->terms()->create($request->validated());

        return to_route('school.calendar.years.edit', $schoolYear)->with('toast', [
            'type' => 'success',
            'message' => 'Term created.',
        ]);
    }

    /**
     * Show the form for editing a term.
     */
    public function edit(Term $term): Response
    {
        return Inertia::render('school/calendar/terms/Edit', [
            'term' => [
                'id' => $term->id,
                'name' => $term->name,
                'start_date' => $term->start_date->toDateString(),
                'end_date' => $term->end_date->toDateString(),
            ],
            'schoolYear' => $this->schoolYearData($term->schoolYear),
        ]);
    }

    /**
     * Update the given term.
     */
    public function update(UpdateTermRequest $request, Term $term): RedirectResponse
    {
        $term->update($request->validated());

        return to_route('school.calendar.years.edit', $term->school_year_id)->with('toast', [
            'type' => 'success',
            'message' => 'Term updated.',
        ]);
    }

    /**
     * The school year data shown on term forms.
     *
     * @return array{id: int, name: string, start_date: string, end_date: string}
     */
    private function schoolYearData(SchoolYear $schoolYear): array
    {
        return [
            'id' => $schoolYear->id,
            'name' => $schoolYear->name,
            'start_date' => $schoolYear->start_date->toDateString(),
            'end_date' => $schoolYear->end_date->toDateString(),
        ];
    }
}
