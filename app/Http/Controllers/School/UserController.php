<?php

namespace App\Http\Controllers\School;

use App\Enums\Role as RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreUserRequest;
use App\Http\Requests\School\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List the school's user accounts.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ]);

        return Inertia::render('school/users/Index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('school/users/Create', [
            'roles' => RoleName::names(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $user->syncRoles((array) $request->validated('roles'));

        return to_route('school.users.index')->with('toast', [
            'type' => 'success',
            'message' => 'User created.',
        ]);
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        $user->load('roles:id,name');

        return Inertia::render('school/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ],
            'roles' => RoleName::names(),
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        $user->syncRoles((array) $request->validated('roles'));

        return to_route('school.users.index')->with('toast', [
            'type' => 'success',
            'message' => 'User updated.',
        ]);
    }
}
