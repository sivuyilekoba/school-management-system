<?php

namespace App\Concerns;

use App\Enums\AnnouncementAudience;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait AnnouncementRules
{
    /**
     * Get the validation rules for an announcement.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function announcementRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'published_at' => ['nullable', 'date'],
            'expires_at' => [
                'nullable',
                'date',
                Rule::when($this->filled('published_at'), ['after_or_equal:published_at']),
            ],
        ];
    }
}
