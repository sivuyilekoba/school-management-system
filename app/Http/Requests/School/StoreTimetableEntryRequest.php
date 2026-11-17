<?php

namespace App\Http\Requests\School;

use App\Concerns\TimetableEntryRules;
use App\Models\CourseClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTimetableEntryRequest extends FormRequest
{
    use TimetableEntryRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('timetable.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $courseClass = $this->route('courseClass');

        return $courseClass instanceof CourseClass
            ? $this->entryRules($courseClass)
            : [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $courseClass = $this->route('courseClass');

            if ($courseClass instanceof CourseClass) {
                $this->validateTeacherAvailability($validator, $courseClass);
            }
        });
    }
}
