<?php

namespace App\Http\Controllers\Portal;

use App\Concerns\AnnouncementData;
use App\Concerns\PortalAccess;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    use AnnouncementData, PortalAccess;

    /**
     * Show the student or family portal dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $announcements = Announcement::query()
            ->published()
            ->active()
            ->addressedTo($user)
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Announcement $announcement): array => $this->announcementSummary($announcement))
            ->values();

        return Inertia::render('portal/Dashboard', [
            'students' => $this->portalStudents($user)
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $student->displayName(),
                ])
                ->values(),
            'announcements' => $announcements,
            'isParent' => $user->isParent(),
            'unreadMessages' => $user->receivedMessages()->whereNull('read_at')->count(),
        ]);
    }
}
