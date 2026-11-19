<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Send portal users to their dashboard and show staff the staff dashboard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->isPortalUser() ?? false) {
            return to_route('portal.dashboard');
        }

        return Inertia::render('Dashboard');
    }
}
