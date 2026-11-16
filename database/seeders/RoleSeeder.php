<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * The roles available in the system, mirroring Gibbon's core roles.
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'Administrator',
        'Teacher',
        'Student',
        'Parent',
        'Support Staff',
    ];

    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
