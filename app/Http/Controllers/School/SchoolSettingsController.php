<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\UpdateSchoolSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    /**
     * The settings that can be edited from the school settings page.
     *
     * @var array<int, string>
     */
    protected const EDITABLE_SETTINGS = [
        'name',
        'short_name',
        'email',
        'phone',
        'address',
        'timezone',
        'date_format',
    ];

    /**
     * Show the school settings form.
     */
    public function edit(): Response
    {
        $logoPath = Setting::get('school.logo_path');

        return Inertia::render('school/settings/Edit', [
            'settings' => [
                'name' => Setting::get('school.name'),
                'short_name' => Setting::get('school.short_name'),
                'email' => Setting::get('school.email'),
                'phone' => Setting::get('school.phone'),
                'address' => Setting::get('school.address'),
                'timezone' => Setting::get('school.timezone'),
                'date_format' => Setting::get('school.date_format'),
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            ],
            'timezones' => timezone_identifiers_list(),
            'dateFormats' => config('school.date_formats'),
        ]);
    }

    /**
     * Update the school settings.
     */
    public function update(UpdateSchoolSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach (self::EDITABLE_SETTINGS as $setting) {
            Setting::set('school.'.$setting, $validated[$setting] ?? null);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('school', 'public');

            if ($path !== false) {
                $previousPath = Setting::get('school.logo_path');

                Setting::set('school.logo_path', $path);

                if ($previousPath) {
                    Storage::disk('public')->delete($previousPath);
                }
            }
        }

        return to_route('school.settings.edit')->with('toast', [
            'type' => 'success',
            'message' => 'School settings updated.',
        ]);
    }
}
