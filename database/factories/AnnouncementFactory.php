<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'audience' => fake()->randomElement(AnnouncementAudience::cases()),
            'published_at' => now(),
            'expires_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the announcement is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the announcement has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'published_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }
}
