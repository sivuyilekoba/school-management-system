<?php

namespace App\Concerns;

use App\Enums\Role as RoleName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait RoleValidationRules
{
    /**
     * Get the validation rules used to validate role assignments.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function roleRules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::in(RoleName::names())],
        ];
    }

    /**
     * Ensure only users who can manage roles may assign the Administrator role.
     */
    protected function guardRoleAssignment(Validator $validator): void
    {
        if (! in_array(RoleName::Administrator->value, (array) $this->input('roles'), true)) {
            return;
        }

        if ($this->user()?->can('roles.manage')) {
            return;
        }

        $validator->errors()->add('roles', 'You do not have permission to assign the Administrator role.');
    }
}
