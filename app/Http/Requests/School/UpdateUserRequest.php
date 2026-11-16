<?php

namespace App\Http\Requests\School;

use App\Concerns\ProfileValidationRules;
use App\Concerns\RoleValidationRules;
use App\Enums\Role as RoleName;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($user instanceof User ? $user->id : null),
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
            fn (Validator $validator) => $this->guardSelfDemotion($validator),
        ];
    }

    /**
     * Prevent administrators from removing their own Administrator role.
     */
    private function guardSelfDemotion(Validator $validator): void
    {
        $user = $this->route('user');
        $actor = $this->user();

        if (! $user instanceof User || $actor === null || ! $user->is($actor)) {
            return;
        }

        if (! $user->hasRole(RoleName::Administrator->value)) {
            return;
        }

        if (in_array(RoleName::Administrator->value, (array) $this->input('roles'), true)) {
            return;
        }

        $validator->errors()->add('roles', 'You cannot remove the Administrator role from your own account.');
    }
}
