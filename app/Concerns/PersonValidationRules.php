<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PersonValidationRules
{
    /**
     * Get the validation rules shared by students and staff.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function personRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Get the rules for linking an optional user account to a person record.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function linkedAccountRules(string $table, ?int $ignoreId = null): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique($table, 'user_id')->ignore($ignoreId),
            ],
        ];
    }
}
