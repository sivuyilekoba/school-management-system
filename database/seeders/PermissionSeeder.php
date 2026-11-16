<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's permissions and grant them to administrators.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissionNames();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName(RoleName::Administrator->value, 'web')
            ->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Flatten the permission catalogue into a list of permission names.
     *
     * @return array<int, string>
     */
    private function permissionNames(): array
    {
        $names = [];

        foreach ((array) config('permissions') as $module) {
            if (! is_array($module)) {
                continue;
            }

            $permissions = $module['permissions'] ?? [];

            if (! is_array($permissions)) {
                continue;
            }

            foreach (array_keys($permissions) as $name) {
                if (is_string($name)) {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }
}
