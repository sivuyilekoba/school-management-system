<?php

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\Family;
use App\Models\Message;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create(['name' => 'Alice Admin']);
    $this->administrator->assignRole('Administrator');

    $this->family = Family::factory()->create(['name' => 'The Aardvark family']);

    $this->student = Student::factory()->create([
        'first_name' => 'Amara',
        'surname' => 'Aardvark',
        'family_id' => $this->family->id,
    ]);

    $this->studentUser = User::factory()->create(['name' => 'Amara Aardvark']);
    $this->studentUser->assignRole('Student');
    $this->student->update(['user_id' => $this->studentUser->id]);

    $this->sibling = Student::factory()->create([
        'first_name' => 'Bola',
        'surname' => 'Aardvark',
        'family_id' => $this->family->id,
    ]);

    $this->parentUser = User::factory()->create(['name' => 'Pat Parent']);
    $this->parentUser->assignRole('Parent');
    $this->family->update(['user_id' => $this->parentUser->id]);

    $this->outsider = Student::factory()->create([
        'first_name' => 'Zara',
        'surname' => 'Zebra',
    ]);

    $this->term = Term::factory()->create([
        'name' => 'Term 1',
        'start_date' => '2026-09-01',
        'end_date' => '2026-12-04',
    ]);
});

/**
 * The announcement titles in the given Inertia prop, sorted for comparison.
 *
 * @return array<int, string>
 */
function announcementTitles(mixed $announcements): array
{
    return collect($announcements)
        ->pluck('title')
        ->sort()
        ->values()
        ->all();
}

test('guests are redirected to the login page', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
    $this->get(route('portal.announcements.index'))->assertRedirect(route('login'));
    $this->get(route('portal.reports.index'))->assertRedirect(route('login'));
    $this->get(route('portal.reports.show', [$this->student, $this->term]))
        ->assertRedirect(route('login'));
});

test('staff cannot use the portal', function () {
    $this->actingAs($this->administrator)
        ->get(route('portal.dashboard'))
        ->assertForbidden();

    $this->actingAs($this->administrator)
        ->get(route('portal.announcements.index'))
        ->assertForbidden();

    $this->actingAs($this->administrator)
        ->get(route('portal.reports.index'))
        ->assertForbidden();
});

test('the student dashboard shows their record and unread messages', function () {
    Message::factory()->create([
        'sender_id' => $this->administrator->id,
        'recipient_id' => $this->studentUser->id,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/Dashboard')
            ->has('students', 1)
            ->where('students.0.name', 'Amara Aardvark')
            ->where('isParent', false)
            ->where('unreadMessages', 1)
            ->where('auth.unreadMessages', 1));
});

test('the parent dashboard shows every child in the family', function () {
    $this->actingAs($this->parentUser)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/Dashboard')
            ->has('students', 2)
            ->where('students.0.name', 'Amara Aardvark')
            ->where('students.1.name', 'Bola Aardvark')
            ->where('isParent', true));
});

test('the dashboard shows the latest announcements addressed to the reader', function () {
    Announcement::factory()->create([
        'title' => 'Everyone notice',
        'audience' => AnnouncementAudience::Everyone,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Students notice',
        'audience' => AnnouncementAudience::Students,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Families notice',
        'audience' => AnnouncementAudience::Families,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Staff notice',
        'audience' => AnnouncementAudience::Staff,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->draft()->create([
        'title' => 'Draft notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);
    Announcement::factory()->expired()->create([
        'title' => 'Expired notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);
    Announcement::factory()->create([
        'title' => 'Future notice',
        'audience' => AnnouncementAudience::Everyone,
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements', fn ($announcements): bool => announcementTitles($announcements) === [
                'Everyone notice',
                'Students notice',
            ]));

    $this->actingAs($this->parentUser)
        ->get(route('portal.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements', fn ($announcements): bool => announcementTitles($announcements) === [
                'Everyone notice',
                'Families notice',
            ]));
});

test('portal announcements are filtered by audience, publication, and expiry', function () {
    Announcement::factory()->create([
        'title' => 'Everyone notice',
        'audience' => AnnouncementAudience::Everyone,
        'published_at' => now()->subDays(2),
    ]);
    Announcement::factory()->create([
        'title' => 'Students notice',
        'audience' => AnnouncementAudience::Students,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->create([
        'title' => 'Families notice',
        'audience' => AnnouncementAudience::Families,
        'published_at' => now()->subDay(),
    ]);
    Announcement::factory()->draft()->create([
        'title' => 'Draft notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);
    Announcement::factory()->expired()->create([
        'title' => 'Expired notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/announcements/Index')
            ->where('announcements.total', 2)
            ->where('announcements.data', fn ($announcements): bool => announcementTitles($announcements) === [
                'Everyone notice',
                'Students notice',
            ]));

    $this->actingAs($this->parentUser)
        ->get(route('portal.announcements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements.total', 2)
            ->where('announcements.data', fn ($announcements): bool => announcementTitles($announcements) === [
                'Everyone notice',
                'Families notice',
            ]));
});

test('portal announcements can be searched', function () {
    Announcement::factory()->create([
        'title' => 'Sports day moved',
        'body' => 'Now on Friday.',
        'audience' => AnnouncementAudience::Everyone,
    ]);
    Announcement::factory()->create([
        'title' => 'Library books',
        'body' => 'Return them this week.',
        'audience' => AnnouncementAudience::Everyone,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.announcements.index', ['q' => 'Friday']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements.total', 1)
            ->where('announcements.data.0.title', 'Sports day moved')
            ->where('search', 'Friday'));
});

test('portal users can only open announcements addressed to them', function () {
    $studentsNotice = Announcement::factory()->create([
        'title' => 'Students notice',
        'audience' => AnnouncementAudience::Students,
    ]);
    $familiesNotice = Announcement::factory()->create([
        'title' => 'Families notice',
        'audience' => AnnouncementAudience::Families,
    ]);
    $expiredNotice = Announcement::factory()->expired()->create([
        'title' => 'Expired notice',
        'audience' => AnnouncementAudience::Everyone,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.announcements.show', $studentsNotice))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/announcements/Show')
            ->where('announcement.title', 'Students notice')
            ->has('announcement.body'));

    $this->actingAs($this->studentUser)
        ->get(route('portal.announcements.show', $familiesNotice))
        ->assertNotFound();

    $this->actingAs($this->studentUser)
        ->get(route('portal.announcements.show', $expiredNotice))
        ->assertNotFound();

    $this->actingAs($this->parentUser)
        ->get(route('portal.announcements.show', $familiesNotice))
        ->assertOk();
});

test('the report list only includes published cards for the readers own students', function () {
    $secondTerm = Term::factory()->create([
        'name' => 'Term 2',
        'start_date' => '2027-01-12',
        'end_date' => '2027-03-26',
    ]);

    ReportCard::factory()->published()->create([
        'student_id' => $this->student->id,
        'term_id' => $this->term->id,
    ]);
    ReportCard::factory()->published()->create([
        'student_id' => $this->sibling->id,
        'term_id' => $this->term->id,
    ]);
    ReportCard::factory()->published()->create([
        'student_id' => $this->outsider->id,
        'term_id' => $this->term->id,
    ]);
    ReportCard::factory()->create([
        'student_id' => $this->student->id,
        'term_id' => $secondTerm->id,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/reports/Index')
            ->has('reportCards', 1)
            ->where('reportCards.0.student.name', 'Amara Aardvark')
            ->where('reportCards.0.term.name', 'Term 1'));

    $this->actingAs($this->parentUser)
        ->get(route('portal.reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reportCards', 2)
            ->where('reportCards.0.student.name', 'Amara Aardvark')
            ->where('reportCards.0.term.school_year', $this->term->schoolYear->name)
            ->where('reportCards.1.student.name', 'Bola Aardvark'));
});

test('a student can only open their own published report card', function () {
    $secondTerm = Term::factory()->create([
        'name' => 'Term 2',
        'start_date' => '2027-01-12',
        'end_date' => '2027-03-26',
    ]);

    ReportCard::factory()->published()->create([
        'student_id' => $this->student->id,
        'term_id' => $this->term->id,
        'comment' => 'A strong term.',
    ]);
    ReportCard::factory()->create([
        'student_id' => $this->student->id,
        'term_id' => $secondTerm->id,
    ]);
    ReportCard::factory()->published()->create([
        'student_id' => $this->outsider->id,
        'term_id' => $this->term->id,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.show', [$this->student, $this->term]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/reports/Show')
            ->where('student.name', 'Amara Aardvark')
            ->where('term.name', 'Term 1')
            ->where('reportCard.published', true)
            ->where('reportCard.comment', 'A strong term.')
            ->where('backUrl', route('portal.reports.index')));

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.show', [$this->student, $secondTerm]))
        ->assertNotFound();

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.show', [$this->outsider, $this->term]))
        ->assertNotFound();
});

test('a parent can open the published report cards for their children', function () {
    ReportCard::factory()->published()->create([
        'student_id' => $this->student->id,
        'term_id' => $this->term->id,
    ]);
    ReportCard::factory()->published()->create([
        'student_id' => $this->sibling->id,
        'term_id' => $this->term->id,
    ]);
    ReportCard::factory()->published()->create([
        'student_id' => $this->outsider->id,
        'term_id' => $this->term->id,
    ]);

    $this->actingAs($this->parentUser)
        ->get(route('portal.reports.show', [$this->sibling, $this->term]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('student.name', 'Bola Aardvark'));

    $this->actingAs($this->parentUser)
        ->get(route('portal.reports.show', [$this->outsider, $this->term]))
        ->assertNotFound();
});

test('a portal user can print their published report card', function () {
    ReportCard::factory()->published()->create([
        'student_id' => $this->student->id,
        'term_id' => $this->term->id,
        'comment' => 'A strong term.',
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.print', [$this->student, $this->term]))
        ->assertOk()
        ->assertSee('Amara Aardvark')
        ->assertSee('A strong term.')
        ->assertSee('window.print()', false)
        ->assertSee('Back to report card');

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.print', [$this->outsider, $this->term]))
        ->assertNotFound();
});

test('a portal user can download a published report card as a pdf', function () {
    Pdf::fake();

    ReportCard::factory()->published()->create([
        'student_id' => $this->student->id,
        'term_id' => $this->term->id,
    ]);

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.pdf', [$this->student, $this->term]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'school.reports.print'
        && $pdf->viewData['forPdf'] === true);

    $this->actingAs($this->studentUser)
        ->get(route('portal.reports.pdf', [$this->outsider, $this->term]))
        ->assertNotFound();
});
