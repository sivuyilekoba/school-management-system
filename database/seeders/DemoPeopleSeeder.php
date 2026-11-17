<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoPeopleSeeder extends Seeder
{
    /**
     * Seed demo families, students and staff for local development.
     */
    public function run(): void
    {
        Family::factory()->count(3)->create()->each(function (Family $family): void {
            Student::factory()->count(2)->create(['family_id' => $family->id]);
        });

        Staff::factory()->count(3)->create();
    }
}
