<?php

namespace App\Http\Controllers\School;

use App\Enums\Role as RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionsController extends Controller
{
    /**
     * Show the permission matrix.
     */
    public function edit(): Response
    {
        $roles = Role::query()
            ->whereIn('name', RoleName::managedNames())
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('school/permissions/Edit', [
            'modules' => config('permissions'),
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
            'assignments' => $roles->mapWithKeys(
                fn (Role $role): array => [$role->name => $role->permissions->pluck('name')->all()],
            ),
        ]);
    }

    /**
     * Replace the permissions granted to each managed role.
     */
    public function update(UpdateRolePermissionsRequest $request): RedirectResponse
    {
        $submitted = (array) $request->validated('permissions');

        foreach (RoleName::managedNames() as $roleName) {
            Role::findByName($roleName, 'web')
                ->syncPermissions((array) ($submitted[$roleName] ?? []));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('school.permissions.edit')->with('toast', [
            'type' => 'success',
            'message' => 'Permissions updated.',
        ]);
    }
}
