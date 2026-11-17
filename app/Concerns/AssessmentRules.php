<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait AssessmentRules
{
    /**
     * Get the validation rules for an assessment.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function assessmentRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'assessed_on' => ['required', 'date_format:Y-m-d'],
            'max_score' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
        ];
    }
}
