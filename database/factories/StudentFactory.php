<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => null,
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'preferred_name' => null,
            'gender' => fake()->randomElement(Gender::names()),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'email' => fake()->safeEmail(),
        ];
    }
}
