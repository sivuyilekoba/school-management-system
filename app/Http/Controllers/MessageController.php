<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * List the current user's conversations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $messages = Message::query()
            ->involving($user)
            ->with(['sender:id,name,email', 'recipient:id,name,email'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $conversations = $messages
            ->groupBy(fn (Message $message): int => $message->sender_id === $user->id
                ? $message->recipient_id
                : $message->sender_id)
            ->map(function (Collection $group) use ($user): array {
                /** @var Message $latest */
                $latest = $group->first();

                $participant = $latest->sender_id === $user->id
                    ? $latest->recipient
                    : $latest->sender;

                return [
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'email' => $participant->email,
                    ],
                    'latest' => [
                        'body' => Str::limit($latest->body, 120),
                        'sent_at' => $latest->created_at?->format('Y-m-d H:i'),
                        'outgoing' => $latest->sender_id === $user->id,
                    ],
                    'unread' => $group
                        ->filter(fn (Message $message): bool => $message->recipient_id === $user->id
                            && ! $message->isRead())
                        ->count(),
                    'sort_key' => $latest->created_at?->toIso8601String() ?? '',
                ];
            })
            ->sortByDesc('sort_key')
            ->values();

        return Inertia::render('messages/Index', [
            'conversations' => $conversations,
            'unreadMessages' => $user->receivedMessages()->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Show the form for writing a new message.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        return Inertia::render('messages/Create', [
            'recipients' => $this->recipients($user),
            'preselected' => $request->integer('to') ?: null,
        ]);
    }

    /**
     * Send a message to another user.
     */
    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $recipient = User::query()->findOrFail($request->integer('recipient_id'));

        Message::query()->create([
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
            'body' => $request->validated('body'),
        ]);

        return to_route('messages.show', $recipient)->with('toast', [
            'type' => 'success',
            'message' => 'Message sent.',
        ]);
    }

    /**
     * Show the conversation with another user.
     */
    public function show(Request $request, User $user): Response
    {
        $currentUser = $request->user();

        abort_if($currentUser === null, 403);
        abort_if($currentUser->is($user), 404);

        $hasConversation = Message::query()
            ->between($currentUser, $user)
            ->exists();

        abort_unless($hasConversation || $currentUser->canMessage($user), 403);

        Message::query()
            ->where('sender_id', $user->id)
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::query()
            ->between($currentUser, $user)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'outgoing' => $message->sender_id === $currentUser->id,
                'sent_at' => $message->created_at?->format('Y-m-d H:i'),
                'read' => $message->isRead(),
            ])
            ->values();

        return Inertia::render('messages/Show', [
            'participant' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'messages' => $messages,
            'canReply' => $currentUser->canMessage($user),
        ]);
    }

    /**
     * The users the current user is allowed to message.
     *
     * @return Collection<int, array{id: int, name: string, email: string, role: string|null}>
     */
    private function recipients(User $user): Collection
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('roles')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->filter(fn (User $recipient): bool => $user->canMessage($recipient))
            ->values()
            ->map(function (User $recipient): array {
                $role = $recipient->getRoleNames()->first();

                return [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                    'role' => is_string($role) ? $role : null,
                ];
            });
    }
}
