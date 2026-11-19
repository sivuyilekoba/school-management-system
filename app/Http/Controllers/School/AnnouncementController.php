<?php

namespace App\Http\Controllers\School;

use App\Concerns\AnnouncementData;
use App\Enums\AnnouncementAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreAnnouncementRequest;
use App\Http\Requests\School\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    use AnnouncementData;

    /**
     * List the announcements visible to the current user.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $announcements = Announcement::query()
            ->with('author:id,name')
            ->when(
                ! $request->user()?->can('announcements.manage'),
                function (Builder $query): void {
                    $query->published();
                },
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Announcement $announcement): array => $this->announcementSummary($announcement));

        return Inertia::render('school/announcements/Index', [
            'announcements' => $announcements,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating an announcement.
     */
    public function create(): Response
    {
        return Inertia::render('school/announcements/Create', [
            'audiences' => AnnouncementAudience::options(),
            'defaults' => [
                'published_at' => now()->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $announcement = Announcement::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        return to_route('school.announcements.show', $announcement)->with('toast', [
            'type' => 'success',
            'message' => 'Announcement created.',
        ]);
    }

    /**
     * Show a single announcement.
     */
    public function show(Request $request, Announcement $announcement): Response
    {
        $announcement->loadMissing('author:id,name');

        if (! $announcement->isVisible() && ! $request->user()?->can('announcements.manage')) {
            abort(404);
        }

        return Inertia::render('school/announcements/Show', [
            'announcement' => [
                ...$this->announcementSummary($announcement),
                'body' => $announcement->body,
                'created_at' => $announcement->created_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Show the form for editing an announcement.
     */
    public function edit(Announcement $announcement): Response
    {
        return Inertia::render('school/announcements/Edit', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'audience' => $announcement->audience->value,
                'published_at' => $announcement->published_at?->format('Y-m-d\TH:i'),
                'expires_at' => $announcement->expires_at?->format('Y-m-d\TH:i'),
            ],
            'audiences' => AnnouncementAudience::options(),
        ]);
    }

    /**
     * Update the given announcement.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return to_route('school.announcements.show', $announcement)->with('toast', [
            'type' => 'success',
            'message' => 'Announcement updated.',
        ]);
    }

    /**
     * Delete the given announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return to_route('school.announcements.index')->with('toast', [
            'type' => 'success',
            'message' => 'Announcement deleted.',
        ]);
    }

    /**
     * Publish an announcement immediately.
     */
    public function publish(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['published_at' => now()]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Announcement published.',
        ]);
    }

    /**
     * Return a published announcement to draft.
     */
    public function unpublish(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['published_at' => null]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Announcement unpublished.',
        ]);
    }
}
