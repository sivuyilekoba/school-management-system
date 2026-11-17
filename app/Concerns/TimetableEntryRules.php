<?php

namespace App\Concerns;

use App\Models\CourseClass;
use App\Models\TimetableEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait TimetableEntryRules
{
    /**
     * Get the validation rules for a timetable entry.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function entryRules(CourseClass $courseClass, ?TimetableEntry $entry = null): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,5'],
            'timetable_period_id' => [
                'required',
                'integer',
                Rule::exists('timetable_periods', 'id'),
                Rule::unique('timetable_entries', 'timetable_period_id')
                    ->where('course_class_id', $courseClass->id)
                    ->where('day_of_week', $this->input('day_of_week'))
                    ->ignore($entry?->id),
            ],
            'room' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Ensure the class teacher is not already teaching elsewhere at this time.
     */
    protected function validateTeacherAvailability(
        Validator $validator,
        CourseClass $courseClass,
        ?TimetableEntry $entry = null,
    ): void {
        if ($validator->errors()->has('day_of_week') || $validator->errors()->has('timetable_period_id')) {
            return;
        }

        if ($courseClass->staff_id === null) {
            return;
        }

        $clash = TimetableEntry::query()
            ->where('day_of_week', (int) $this->input('day_of_week'))
            ->where('timetable_period_id', (int) $this->input('timetable_period_id'))
            ->where('course_class_id', '!=', $courseClass->id)
            ->whereHas('courseClass', fn (Builder $query): Builder => $query
                ->where('staff_id', $courseClass->staff_id));

        if ($entry !== null) {
            $clash->whereKeyNot($entry->id);
        }

        if ($clash->exists()) {
            $validator->errors()->add(
                'timetable_period_id',
                'This teacher is already teaching another class at that time.',
            );
        }
    }
}
