<?php

namespace App\Http\Controllers\Portal;

use App\Concerns\AnnouncementData;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    use AnnouncementData;

    /**
     * List the announcements addressed to the current portal user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $search = trim((string) $request->query('q', ''));

        $announcements = Announcement::query()
            ->published()
            ->active()
            ->addressedTo($user)
            ->with('author:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Announcement $announcement): array => $this->announcementSummary($announcement));

        return Inertia::render('portal/announcements/Index', [
            'announcements' => $announcements,
            'search' => $search,
        ]);
    }

    /**
     * Show an announcement addressed to the current portal user.
     */
    public function show(Request $request, Announcement $announcement): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $isAvailable = $announcement->isVisible()
            && ! $announcement->isExpired()
            && $announcement->isAddressedTo($user);

        abort_unless($isAvailable, 404);

        $announcement->loadMissing('author:id,name');

        return Inertia::render('portal/announcements/Show', [
            'announcement' => [
                ...$this->announcementSummary($announcement),
                'body' => $announcement->body,
            ],
        ]);
    }
}
