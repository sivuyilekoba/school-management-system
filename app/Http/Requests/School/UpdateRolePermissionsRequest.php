<?php

namespace App\Http\Requests\School;

use App\Enums\Role as RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $managedRoles = implode(',', RoleName::managedNames());

        return [
            'permissions' => [
                'required',
                'array:'.$managedRoles,
                'required_array_keys:'.$managedRoles,
            ],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}
