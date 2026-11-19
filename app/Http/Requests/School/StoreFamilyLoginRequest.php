<?php

namespace App\Http\Requests\School;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StoreFamilyLoginRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to create a family login.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('people.families.manage') ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => $this->emailRules(),
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
