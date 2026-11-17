<?php

namespace App\Http\Requests\School;

use App\Models\SchoolYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolYearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('calendar.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolYear = $this->route('schoolYear');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('school_years', 'name')
                    ->ignore($schoolYear instanceof SchoolYear ? $schoolYear->id : null),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
        ];
    }
}
