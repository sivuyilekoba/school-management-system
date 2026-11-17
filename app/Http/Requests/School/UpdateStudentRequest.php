<?php

namespace App\Http\Requests\School;

use App\Concerns\PersonValidationRules;
use App\Enums\Gender;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    use PersonValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('people.students.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            ...$this->personRules(),
            'gender' => ['required', 'string', Rule::in(Gender::names())],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'family_id' => ['nullable', 'integer', Rule::exists('families', 'id')],
            ...$this->linkedAccountRules('students', $student instanceof Student ? $student->id : null),
        ];
    }
}
