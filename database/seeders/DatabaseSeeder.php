<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        $this->seedDefaultSettings();

        if (app()->environment('local')) {
            $this->seedDemoAdministrator();
        }
    }

    /**
     * Store the default school settings if they do not exist yet.
     */
    private function seedDefaultSettings(): void
    {
        foreach (config('school.defaults') as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(Setting::CACHE_KEY);
    }

    /**
     * Create a demo administrator for local development.
     */
    private function seedDemoAdministrator(): void
    {
        $administrator = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'School Administrator', 'password' => 'password'],
        );

        $administrator->assignRole('Administrator');
    }
}
