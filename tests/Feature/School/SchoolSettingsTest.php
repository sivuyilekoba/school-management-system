<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();

    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Administrator');
});

test('guests are redirected to the login page', function () {
    $this->get(route('school.settings.edit'))->assertRedirect(route('login'));
});

test('users without the settings permission are forbidden', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->get(route('school.settings.edit'))
        ->assertForbidden();
});

test('administrators can view the school settings page', function () {
    $this->actingAs($this->administrator)
        ->get(route('school.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('school/settings/Edit')
            ->where('settings.name', 'School Management System')
            ->where('settings.timezone', 'UTC')
            ->where('settings.date_format', 'd/m/Y')
            ->has('timezones')
            ->has('dateFormats'));
});

test('administrators can update the school settings', function () {
    $this->actingAs($this->administrator)
        ->patch(route('school.settings.update'), [
            'name' => 'Springfield High School',
            'short_name' => 'SHS',
            'email' => 'office@springfield.example',
            'phone' => '+1 555 0100',
            'address' => '1 Main Street',
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('school.settings.edit'))
        ->assertSessionHas('toast', [
            'type' => 'success',
            'message' => 'School settings updated.',
        ]);

    expect(Setting::get('school.name'))->toBe('Springfield High School')
        ->and(Setting::get('school.short_name'))->toBe('SHS')
        ->and(Setting::get('school.email'))->toBe('office@springfield.example')
        ->and(Setting::get('school.phone'))->toBe('+1 555 0100')
        ->and(Setting::get('school.address'))->toBe('1 Main Street')
        ->and(Setting::get('school.timezone'))->toBe('America/New_York')
        ->and(Setting::get('school.date_format'))->toBe('Y-m-d');
});

test('users without the settings permission cannot update the school settings', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('Teacher');

    $this->actingAs($teacher)
        ->patch(route('school.settings.update'), [
            'name' => 'Hijacked School',
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
        ])
        ->assertForbidden();

    expect(Setting::get('school.name'))->toBe('School Management System');
});

test('an invalid timezone is rejected', function () {
    $this->actingAs($this->administrator)
        ->patch(route('school.settings.update'), [
            'name' => 'Springfield High School',
            'timezone' => 'Mars/Olympus_Mons',
            'date_format' => 'd/m/Y',
        ])
        ->assertSessionHasErrors('timezone');

    expect(Setting::get('school.name'))->toBe('School Management System');
});

test('an unsupported date format is rejected', function () {
    $this->actingAs($this->administrator)
        ->patch(route('school.settings.update'), [
            'name' => 'Springfield High School',
            'timezone' => 'UTC',
            'date_format' => 'Y',
        ])
        ->assertSessionHasErrors('date_format');
});

test('the logo is stored and the previous logo is removed', function () {
    Storage::fake('public');

    Setting::set('school.logo_path', 'school/old-logo.png');
    Storage::disk('public')->put('school/old-logo.png', 'old');

    $this->actingAs($this->administrator)
        ->patch(route('school.settings.update'), [
            'name' => 'Springfield High School',
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])
        ->assertSessionHasNoErrors();

    $path = Setting::get('school.logo_path');

    expect($path)->not->toBeNull()
        ->and($path)->not->toBe('school/old-logo.png');

    Storage::disk('public')->assertExists($path);
    Storage::disk('public')->assertMissing('school/old-logo.png');
});

test('the settings cache is refreshed after an update', function () {
    expect(Setting::get('school.name'))->toBe('School Management System');

    $this->actingAs($this->administrator)
        ->patch(route('school.settings.update'), [
            'name' => 'Springfield High School',
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::get('school.name'))->toBe('Springfield High School');
});
