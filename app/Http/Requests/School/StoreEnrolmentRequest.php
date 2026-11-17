<?php

namespace App\Http\Requests\School;

use App\Models\CourseClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrolmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('curriculum.enrolment.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $courseClass = $this->route('courseClass');

        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id'),
                Rule::unique('enrolments', 'student_id')
                    ->where('course_class_id', $courseClass instanceof CourseClass ? $courseClass->id : null),
            ],
        ];
    }
}
