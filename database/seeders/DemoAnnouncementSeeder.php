<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAnnouncementSeeder extends Seeder
{
    /**
     * Seed published, draft, and expired announcements for local development.
     */
    public function run(): void
    {
        $authorId = User::query()->where('email', 'admin@example.com')->value('id');

        $announcements = [
            [
                'title' => 'Welcome to the new school year',
                'body' => "Welcome back, everyone. The new school year starts this week and we are looking forward to seeing you all.\n\nPlease check the timetable for your classes and speak to your teacher if anything looks wrong.",
                'audience' => AnnouncementAudience::Everyone,
                'published_at' => now()->subDays(3),
                'expires_at' => null,
            ],
            [
                'title' => 'Staff meeting agenda',
                'body' => "This week's staff meeting covers report cards, attendance follow-up, and the term two calendar.\n\nPlease bring any concerns about your classes to the meeting.",
                'audience' => AnnouncementAudience::Staff,
                'published_at' => null,
                'expires_at' => null,
            ],
            [
                'title' => 'Term 1 exam timetable',
                'body' => 'The term one examination timetable has now closed. Speak to the office if you need a copy for your records.',
                'audience' => AnnouncementAudience::Students,
                'published_at' => now()->subDays(60),
                'expires_at' => now()->subDays(30),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::query()->updateOrCreate(
                ['title' => $announcement['title']],
                [
                    ...$announcement,
                    'created_by' => $authorId,
                ],
            );
        }
    }
}
