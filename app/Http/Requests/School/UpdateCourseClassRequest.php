<?php

namespace App\Http\Requests\School;

use App\Models\CourseClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('curriculum.manage') ?? false;
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('course_classes', 'name')
                    ->where('course_id', $courseClass instanceof CourseClass ? $courseClass->course_id : null)
                    ->ignore($courseClass instanceof CourseClass ? $courseClass->id : null),
            ],
            'staff_id' => ['nullable', 'integer', Rule::exists('staff', 'id')],
        ];
    }
}
