<?php

namespace App\Http\Requests\School;

use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
        $course = $this->route('course');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')
                    ->ignore($course instanceof Course ? $course->id : null),
            ],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
