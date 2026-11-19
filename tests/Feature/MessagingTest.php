<?php

use App\Models\Family;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create(['name' => 'Alice Admin']);
    $this->administrator->assignRole('Administrator');

    $this->teacher = User::factory()->create(['name' => 'Tara Teacher']);
    $this->teacher->assignRole('Teacher');

    $this->support = User::factory()->create(['name' => 'Sven Support']);
    $this->support->assignRole('Support Staff');

    $this->studentUser = User::factory()->create(['name' => 'Sam Student']);
    $this->studentUser->assignRole('Student');
    $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);

    $this->parentUser = User::factory()->create(['name' => 'Pat Parent']);
    $this->parentUser->assignRole('Parent');
    $this->family = Family::factory()->create(['user_id' => $this->parentUser->id]);
});

/**
 * Store a message with a deterministic timestamp.
 */
function sendDemoMessage(
    User $sender,
    User $recipient,
    string $body,
    ?CarbonInterface $sentAt = null,
    ?CarbonInterface $readAt = null,
): Message {
    $message = Message::factory()->create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'body' => $body,
        'read_at' => $readAt,
    ]);

    if ($sentAt instanceof CarbonInterface) {
        $message->forceFill([
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ])->save();
    }

    return $message;
}

test('guests are redirected to the login page', function () {
    $this->get(route('messages.index'))->assertRedirect(route('login'));
    $this->get(route('messages.create'))->assertRedirect(route('login'));
    $this->get(route('messages.show', $this->teacher))->assertRedirect(route('login'));
    $this->post(route('messages.store'), [
        'recipient_id' => $this->teacher->id,
        'body' => 'Hello.',
    ])->assertRedirect(route('login'));
});

test('users without messaging access cannot use the inbox', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('messages.index'))
        ->assertForbidden();

    $this->actingAs($this->teacher)
        ->get(route('messages.index'))
        ->assertForbidden();
});

test('staff with the messages view permission can use the inbox', function () {
    $this->teacher->givePermissionTo('messages.view');
    $this->teacher->givePermissionTo('messages.send');

    $this->actingAs($this->teacher)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('messages/Index')
            ->has('conversations', 0)
            ->where('unreadMessages', 0)
            ->where('auth.unreadMessages', 0));
});

test('portal users can use the inbox without explicit permissions', function () {
    $this->actingAs($this->studentUser)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('messages/Index'));

    $this->actingAs($this->parentUser)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('messages/Index'));
});

test('staff can only choose staff and portal users as recipients', function () {
    $this->actingAs($this->administrator)
        ->get(route('messages.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('messages/Create')
            ->has('recipients', 4)
            ->where('recipients.0.name', 'Pat Parent')
            ->where('recipients.1.name', 'Sam Student')
            ->where('recipients.2.name', 'Sven Support')
            ->where('recipients.3.name', 'Tara Teacher'));
});

test('students and parents can only choose staff as recipients', function () {
    $this->actingAs($this->studentUser)
        ->get(route('messages.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recipients', 3)
            ->where('recipients.0.name', 'Alice Admin')
            ->where('recipients.1.name', 'Sven Support')
            ->where('recipients.2.role', 'Teacher'));

    $this->actingAs($this->parentUser)
        ->get(route('messages.create', ['to' => $this->teacher->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recipients', 3)
            ->where('preselected', $this->teacher->id));
});

test('the inbox groups messages into conversations with unread counts', function () {
    sendDemoMessage($this->teacher, $this->studentUser, 'First note.', now()->subMinutes(10));
    sendDemoMessage($this->teacher, $this->studentUser, 'Second note.', now()->subMinutes(5));
    sendDemoMessage($this->studentUser, $this->teacher, 'My reply.', now()->subMinutes(4), now()->subMinutes(4));
    sendDemoMessage($this->administrator, $this->studentUser, 'From the office.', now()->subMinutes(30), now()->subMinutes(30));

    $this->actingAs($this->studentUser)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('conversations', 2)
            ->where('conversations.0.participant.name', 'Tara Teacher')
            ->where('conversations.0.latest.body', 'My reply.')
            ->where('conversations.0.latest.outgoing', true)
            ->where('conversations.0.unread', 2)
            ->where('conversations.1.participant.name', 'Alice Admin')
            ->where('conversations.1.unread', 0)
            ->where('unreadMessages', 2)
            ->where('auth.unreadMessages', 2));
});

test('a student can message a member of staff', function () {
    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->teacher->id,
            'body' => 'Could you explain the homework?',
        ])
        ->assertRedirect(route('messages.show', $this->teacher))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'Message sent.',
        ]);

    $message = Message::query()->firstOrFail();

    expect($message->sender_id)->toBe($this->studentUser->id)
        ->and($message->recipient_id)->toBe($this->teacher->id)
        ->and($message->body)->toBe('Could you explain the homework?')
        ->and($message->read_at)->toBeNull();
});

test('a parent can message a member of staff', function () {
    $this->actingAs($this->parentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->administrator->id,
            'body' => 'Please update our contact details.',
        ])
        ->assertRedirect(route('messages.show', $this->administrator))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('messages', [
        'sender_id' => $this->parentUser->id,
        'recipient_id' => $this->administrator->id,
    ]);
});

test('staff can message portal users and other staff', function () {
    $this->actingAs($this->administrator)
        ->post(route('messages.store'), [
            'recipient_id' => $this->studentUser->id,
            'body' => 'Your report card is ready.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->administrator)
        ->post(route('messages.store'), [
            'recipient_id' => $this->teacher->id,
            'body' => 'Please review the marks.',
        ])
        ->assertSessionHasNoErrors();

    expect(Message::query()->count())->toBe(2);
});

test('portal users cannot message other portal users or themselves', function () {
    $otherStudentUser = User::factory()->create();
    $otherStudentUser->assignRole('Student');

    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $otherStudentUser->id,
            'body' => 'Hello classmate.',
        ])
        ->assertSessionHasErrors('recipient_id');

    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->parentUser->id,
            'body' => 'Hello parent.',
        ])
        ->assertSessionHasErrors('recipient_id');

    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->studentUser->id,
            'body' => 'Note to self.',
        ])
        ->assertSessionHasErrors('recipient_id');

    expect(Message::query()->count())->toBe(0);
});

test('sending a message validates its body and recipient', function () {
    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->teacher->id,
            'body' => '',
        ])
        ->assertSessionHasErrors('body');

    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => 999999,
            'body' => 'Hello.',
        ])
        ->assertSessionHasErrors('recipient_id');

    $this->actingAs($this->studentUser)
        ->post(route('messages.store'), [
            'recipient_id' => $this->teacher->id,
            'body' => str_repeat('a', 5001),
        ])
        ->assertSessionHasErrors('body');

    expect(Message::query()->count())->toBe(0);
});

test('teachers without the messages send permission cannot post messages', function () {
    $this->teacher->givePermissionTo('messages.view');

    $this->actingAs($this->teacher)
        ->post(route('messages.store'), [
            'recipient_id' => $this->studentUser->id,
            'body' => 'Hello.',
        ])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});

test('opening a conversation marks received messages as read', function () {
    sendDemoMessage($this->teacher, $this->studentUser, 'First note.', now()->subMinutes(10));
    sendDemoMessage($this->teacher, $this->studentUser, 'Second note.', now()->subMinutes(5));
    sendDemoMessage($this->studentUser, $this->teacher, 'My reply.', now()->subMinutes(4), now()->subMinutes(4));

    $this->actingAs($this->studentUser)
        ->get(route('messages.show', $this->teacher))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('messages/Show')
            ->where('participant.name', 'Tara Teacher')
            ->has('messages', 3)
            ->where('messages.2.body', 'My reply.')
            ->where('messages.2.outgoing', true)
            ->where('canReply', true));

    expect(Message::query()->whereNull('read_at')->count())->toBe(0);
});

test('a user can start a new conversation with someone they may message', function () {
    $this->actingAs($this->studentUser)
        ->get(route('messages.show', $this->teacher))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('messages', 0)
            ->where('canReply', true));
});

test('a user cannot open an inaccessible conversation', function () {
    $otherStudentUser = User::factory()->create();
    $otherStudentUser->assignRole('Student');

    $this->actingAs($this->studentUser)
        ->get(route('messages.show', $otherStudentUser))
        ->assertForbidden();

    $this->actingAs($this->studentUser)
        ->get(route('messages.show', $this->studentUser))
        ->assertNotFound();
});
