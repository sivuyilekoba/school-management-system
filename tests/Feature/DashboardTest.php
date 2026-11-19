<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\Family;
use App\Models\Message;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the staff dashboard shows permitted counts and latest staff announcements', function () {
    $this->seed();

    $administrator = User::factory()->create();
    $administrator->assignRole('Administrator');

    Family::factory()->count(2)->create();
    Student::factory()->count(3)->create();
    Staff::factory()->count(4)->create();

    Message::factory()->create([
        'sender_id' => User::factory()->create()->id,
        'recipient_id' => $administrator->id,
    ]);

    Announcement::factory()->create([
        'title' => 'Staff briefing',
        'audience' => AnnouncementAudience::Staff,
        'published_at' => now()->subHour(),
    ]);
    Announcement::factory()->create([
        'title' => 'Everyone notice',
        'audience' => AnnouncementAudience::Everyone,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Students only',
        'audience' => AnnouncementAudience::Students,
        'published_at' => now()->subHour(),
    ]);
    Announcement::factory()->draft()->create([
        'title' => 'Draft notice',
        'audience' => AnnouncementAudience::Staff,
    ]);
    Announcement::factory()->expired()->create([
        'title' => 'Expired notice',
        'audience' => AnnouncementAudience::Staff,
    ]);

    $this->actingAs($administrator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.students', 3)
            ->where('stats.staff', 4)
            ->where('stats.families', 2)
            ->where('stats.unreadMessages', 1)
            ->has('announcements', 2)
            ->where('announcements.0.title', 'Staff briefing')
            ->where('announcements.1.title', 'Everyone notice'));
});

test('the dashboard hides data the user cannot view', function () {
    $user = User::factory()->create();

    Announcement::factory()->create([
        'title' => 'Everyone notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.students', null)
            ->where('stats.staff', null)
            ->where('stats.families', null)
            ->where('stats.unreadMessages', null)
            ->has('announcements', 0));
});
