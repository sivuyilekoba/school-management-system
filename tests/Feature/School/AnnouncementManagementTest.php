<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

/**
 * The payload used to create or update an announcement.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function announcementPayload(array $overrides = []): array
{
    return [
        'title' => 'Sports day moved to Friday',
        'body' => 'The sports day has moved to Friday because of the weather forecast.',
        'audience' => AnnouncementAudience::Everyone->value,
        'published_at' => '2026-11-18T09:00',
        'expires_at' => null,
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function () {
    $announcement = Announcement::factory()->create();

    $this->get(route('school.announcements.index'))->assertRedirect(route('login'));
    $this->get(route('school.announcements.show', $announcement))->assertRedirect(route('login'));
});

test('users without the announcements view permission cannot read announcements', function () {
    $announcement = Announcement::factory()->create();

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.announcements.index'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.announcements.show', $announcement))
        ->assertForbidden();
});

test('readers only see published announcements', function () {
    $published = Announcement::factory()->create([
        'title' => 'Published notice',
        'published_at' => now()->subDay(),
    ]);

    $draft = Announcement::factory()->draft()->create([
        'title' => 'Draft notice',
    ]);

    Announcement::factory()->create([
        'title' => 'Scheduled notice',
        'published_at' => now()->addDay(),
    ]);

    Role::findByName('Teacher')->givePermissionTo('announcements.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/announcements/Index')
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Published notice')
            ->where('announcements.data.0.status', 'published'));

    $this->actingAs($teacher)
        ->get(route('school.announcements.show', $published))
        ->assertOk();

    $this->actingAs($teacher)
        ->get(route('school.announcements.show', $draft))
        ->assertNotFound();
});

test('administrators see drafts and scheduled announcements in the list', function () {
    Announcement::factory()->create(['title' => 'Published notice']);
    Announcement::factory()->draft()->create(['title' => 'Draft notice']);
    Announcement::factory()->create([
        'title' => 'Scheduled notice',
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('announcements.data', 3)
            ->where('announcements.total', 3)
            ->where('announcements.data.0.title', 'Scheduled notice')
            ->where('announcements.data.0.status', 'scheduled')
            ->where('announcements.data.1.title', 'Published notice')
            ->where('announcements.data.1.status', 'published')
            ->where('announcements.data.2.title', 'Draft notice')
            ->where('announcements.data.2.status', 'draft'));
});

test('the announcement list can be searched by title or body', function () {
    Announcement::factory()->create([
        'title' => 'Sports day',
        'body' => 'Moved to Friday.',
    ]);
    Announcement::factory()->create([
        'title' => 'Staff meeting',
        'body' => 'Agenda for the week.',
    ]);

    $this->actingAs($this->administrator)
        ->get(route('school.announcements.index', ['q' => 'Sports']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Sports day')
            ->where('search', 'Sports'));

    $this->actingAs($this->administrator)
        ->get(route('school.announcements.index', ['q' => 'Agenda']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Staff meeting'));
});

test('administrators can create an announcement', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.announcements.store'), announcementPayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Announcement created.',
        ]);

    $announcement = Announcement::query()->firstOrFail();

    expect($announcement->title)->toBe('Sports day moved to Friday')
        ->and($announcement->audience)->toBe(AnnouncementAudience::Everyone)
        ->and($announcement->created_by)->toBe($this->administrator->id)
        ->and($announcement->published_at->toDateTimeString())->toBe('2026-11-18 09:00:00');

    $this->actingAs($this->administrator)
        ->get(route('school.announcements.show', $announcement))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/announcements/Show')
            ->where('announcement.title', 'Sports day moved to Friday')
            ->where('announcement.status', 'published')
            ->where('announcement.author', $this->administrator->name));
});

test('an announcement without a publish date is saved as a draft', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.announcements.store'), announcementPayload([
            'published_at' => null,
        ]))
        ->assertSessionHasNoErrors();

    $announcement = Announcement::query()->firstOrFail();

    expect($announcement->published_at)->toBeNull()
        ->and($announcement->status())->toBe('draft');
});

test('creating an announcement validates its fields', function () {
    $this->actingAs($this->administrator)
        ->post(route('school.announcements.store'), announcementPayload([
            'title' => '',
            'body' => '',
        ]))
        ->assertSessionHasErrors(['title', 'body']);

    $this->actingAs($this->administrator)
        ->post(route('school.announcements.store'), announcementPayload([
            'audience' => 'aliens',
        ]))
        ->assertSessionHasErrors('audience');

    $this->actingAs($this->administrator)
        ->post(route('school.announcements.store'), announcementPayload([
            'published_at' => '2026-11-18T09:00',
            'expires_at' => '2026-11-17T09:00',
        ]))
        ->assertSessionHasErrors('expires_at');

    expect(Announcement::query()->count())->toBe(0);
});

test('administrators can update an announcement', function () {
    $announcement = Announcement::factory()->draft()->create([
        'title' => 'Old title',
    ]);

    $this->actingAs($this->administrator)
        ->put(route('school.announcements.update', $announcement), announcementPayload([
            'title' => 'New title',
            'audience' => AnnouncementAudience::Staff->value,
            'published_at' => null,
            'expires_at' => null,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.announcements.show', $announcement))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Announcement updated.',
        ]);

    $announcement->refresh();

    expect($announcement->title)->toBe('New title')
        ->and($announcement->audience)->toBe(AnnouncementAudience::Staff)
        ->and($announcement->published_at)->toBeNull();
});

test('administrators can publish and unpublish an announcement', function () {
    $announcement = Announcement::factory()->draft()->create();

    $this->actingAs($this->administrator)
        ->put(route('school.announcements.publish', $announcement))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Announcement published.',
        ]);

    expect($announcement->refresh()->isVisible())->toBeTrue();

    $this->actingAs($this->administrator)
        ->delete(route('school.announcements.unpublish', $announcement))
        ->assertRedirect()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Announcement unpublished.',
        ]);

    expect($announcement->refresh()->published_at)->toBeNull();
});

test('administrators can delete an announcement', function () {
    $announcement = Announcement::factory()->draft()->create();

    $this->actingAs($this->administrator)
        ->delete(route('school.announcements.destroy', $announcement))
        ->assertRedirect(route('school.announcements.index'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Announcement deleted.',
        ]);

    expect(Announcement::query()->count())->toBe(0);
});

test('readers cannot create, update, publish, or delete announcements', function () {
    $announcement = Announcement::factory()->draft()->create();

    Role::findByName('Teacher')->givePermissionTo('announcements.view');

    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.announcements.create'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('school.announcements.store'), announcementPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->get(route('school.announcements.edit', $announcement))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.announcements.update', $announcement), announcementPayload())
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('school.announcements.publish', $announcement))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->delete(route('school.announcements.destroy', $announcement))
        ->assertForbidden();

    expect(Announcement::query()->count())->toBe(1);
});
