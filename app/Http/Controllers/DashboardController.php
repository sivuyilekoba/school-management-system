<?php

namespace App\Http\Controllers;

use App\Concerns\AnnouncementData;
use App\Models\Announcement;
use App\Models\Family;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AnnouncementData;

    /**
     * Send portal users to their dashboard and show staff the staff dashboard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user instanceof User, 403);

        if ($user->isPortalUser()) {
            return to_route('portal.dashboard');
        }

        $announcements = $user->can('announcements.view')
            ? Announcement::query()
                ->published()
                ->active()
                ->addressedTo($user)
                ->with('author:id,name')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (Announcement $announcement): array => $this->announcementSummary($announcement))
                ->values()
            : collect();

        return Inertia::render('Dashboard', [
            'stats' => [
                'students' => $user->can('people.students.view')
                    ? Student::query()->count()
                    : null,
                'staff' => $user->can('people.staff.view')
                    ? Staff::query()->count()
                    : null,
                'families' => $user->can('people.families.view')
                    ? Family::query()->count()
                    : null,
                'unreadMessages' => $user->can('messages.view')
                    ? $user->receivedMessages()->whereNull('read_at')->count()
                    : null,
            ],
            'announcements' => $announcements,
        ]);
    }
}
