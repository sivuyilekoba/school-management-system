<?php

namespace App\Http\Requests\School;

use App\Concerns\ProfileValidationRules;
use App\Concerns\RoleValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules, RoleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
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
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ...$this->roleRules(),
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->guardRoleAssignment($validator),
        ];
    }
}
