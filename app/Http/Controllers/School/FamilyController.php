<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreFamilyRequest;
use App\Http\Requests\School\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * List the school's families.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $families = Family::query()
            ->withCount('students')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('contact_email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Family $family): array => [
                'id' => $family->id,
                'name' => $family->name,
                'contact_email' => $family->contact_email,
                'contact_phone' => $family->contact_phone,
                'students_count' => $family->students_count,
            ]);

        return Inertia::render('school/families/Index', [
            'families' => $families,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a family.
     */
    public function create(): Response
    {
        return Inertia::render('school/families/Create');
    }

    /**
     * Store a newly created family.
     */
    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        Family::query()->create($request->validated());

        return to_route('school.families.index')->with('toast', [
            'type' => 'success',
            'message' => 'Family created.',
        ]);
    }

    /**
     * Show the form for editing a family.
     */
    public function edit(Family $family): Response
    {
        return Inertia::render('school/families/Edit', [
            'family' => [
                'id' => $family->id,
                'name' => $family->name,
                'contact_email' => $family->contact_email,
                'contact_phone' => $family->contact_phone,
                'students' => $family->students()
                    ->orderBy('surname')
                    ->orderBy('first_name')
                    ->get()
                    ->map(fn (Student $student): array => [
                        'id' => $student->id,
                        'name' => $student->displayName(),
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * Update the given family.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());

        return to_route('school.families.index')->with('toast', [
            'type' => 'success',
            'message' => 'Family updated.',
        ]);
    }
}
