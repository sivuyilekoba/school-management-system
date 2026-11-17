<?php

namespace App\Concerns;

use App\Models\SchoolYear;
use App\Models\Term;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TermValidationRules
{
    /**
     * Get the validation rules for a term within a school year.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function termRules(SchoolYear $schoolYear, ?Term $term = null): array
    {
        $yearStart = $schoolYear->start_date->toDateString();
        $yearEnd = $schoolYear->end_date->toDateString();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('terms', 'name')
                    ->where('school_year_id', $schoolYear->id)
                    ->ignore($term?->id),
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:'.$yearStart,
                'before_or_equal:'.$yearEnd,
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                'before_or_equal:'.$yearEnd,
            ],
        ];
    }
}
