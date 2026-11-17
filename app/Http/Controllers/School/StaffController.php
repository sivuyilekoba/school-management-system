<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreStaffRequest;
use App\Http\Requests\School\UpdateStaffRequest;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    /**
     * List the school's staff members.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $staff = Staff::query()
            ->with('user:id,name,email')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('surname', 'like', '%'.$search.'%')
                        ->orWhere('preferred_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('job_title', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => $staff->displayName(),
                'job_title' => $staff->job_title,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'account' => $staff->user?->email,
            ]);

        return Inertia::render('school/staff/Index', [
            'staff' => $staff,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a staff member.
     */
    public function create(): Response
    {
        return Inertia::render('school/staff/Create', [
            'accounts' => $this->accountOptions(),
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        Staff::query()->create($request->validated());

        return to_route('school.staff.index')->with('toast', [
            'type' => 'success',
            'message' => 'Staff member created.',
        ]);
    }

    /**
     * Show the form for editing a staff member.
     */
    public function edit(Staff $staff): Response
    {
        return Inertia::render('school/staff/Edit', [
            'staff' => [
                'id' => $staff->id,
                'first_name' => $staff->first_name,
                'surname' => $staff->surname,
                'preferred_name' => $staff->preferred_name,
                'job_title' => $staff->job_title,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'user_id' => $staff->user_id,
            ],
            'accounts' => $this->accountOptions($staff),
        ]);
    }

    /**
     * Update the given staff member.
     */
    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validated());

        return to_route('school.staff.index')->with('toast', [
            'type' => 'success',
            'message' => 'Staff member updated.',
        ]);
    }

    /**
     * The user accounts that can be linked to a staff member.
     *
     * @return Collection<int, User>
     */
    private function accountOptions(?Staff $staff = null): Collection
    {
        $linkedAccountIds = Staff::query()
            ->whereNotNull('user_id')
            ->when($staff !== null, function (Builder $query) use ($staff): void {
                $query->where('id', '!=', $staff->id);
            })
            ->pluck('user_id');

        return User::query()
            ->whereNotIn('id', $linkedAccountIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
