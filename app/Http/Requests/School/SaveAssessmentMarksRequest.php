<?php

namespace App\Http\Requests\School;

use App\Models\Assessment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAssessmentMarksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('assessment.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assessment = $this->route('assessment');

        if (! $assessment instanceof Assessment) {
            return [];
        }

        return [
            'records' => ['array'],
            'records.*.student_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('enrolments', 'student_id')
                    ->where('course_class_id', $assessment->course_class_id),
            ],
            'records.*.score' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->max_score],
        ];
    }

    /**
     * The validated marks to save.
     *
     * @return array<int, array{student_id: int, score: float|null}>
     */
    public function markRecords(): array
    {
        $records = $this->validated('records');

        if (! is_array($records)) {
            return [];
        }

        $marks = [];

        foreach ($records as $record) {
            if (! is_array($record) || ! isset($record['student_id'])) {
                continue;
            }

            $score = $record['score'] ?? null;

            $marks[] = [
                'student_id' => (int) $record['student_id'],
                'score' => is_numeric($score) ? (float) $score : null,
            ];
        }

        return $marks;
    }
}
