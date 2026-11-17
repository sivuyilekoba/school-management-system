<?php

namespace App\Http\Requests\School;

use App\Enums\AttendanceStatus;
use App\Models\CourseClass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.record') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $courseClass = $this->route('courseClass');

        if (! $courseClass instanceof CourseClass) {
            return [];
        }

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'records' => ['array'],
            'records.*.student_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('enrolments', 'student_id')
                    ->where('course_class_id', $courseClass->id),
            ],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The validated register date.
     */
    public function attendanceDate(): string
    {
        $date = $this->validated('date');

        return is_string($date) ? $date : now()->toDateString();
    }

    /**
     * The validated attendance marks.
     *
     * @return array<int, array{student_id: int, status: string, note: string|null}>
     */
    public function attendanceRecords(): array
    {
        $records = $this->validated('records');

        if (! is_array($records)) {
            return [];
        }

        $marks = [];

        foreach ($records as $record) {
            if (! is_array($record) || ! isset($record['student_id'], $record['status'])) {
                continue;
            }

            $note = $record['note'] ?? null;

            $marks[] = [
                'student_id' => (int) $record['student_id'],
                'status' => (string) $record['status'],
                'note' => is_string($note) ? $note : null,
            ];
        }

        return $marks;
    }
}
