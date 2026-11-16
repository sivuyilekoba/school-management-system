<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $logoPath = Setting::get('school.logo_path');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'school' => [
                'name' => Setting::get('school.name', config('app.name')),
                'short_name' => Setting::get('school.short_name'),
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'timezone' => Setting::get('school.timezone', config('app.timezone')),
                'date_format' => Setting::get('school.date_format', 'd/m/Y'),
            ],
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()?->getAllPermissions()->pluck('name')->all() ?? [],
            ],
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
