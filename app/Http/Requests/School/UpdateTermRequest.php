<?php

namespace App\Http\Requests\School;

use App\Concerns\TermValidationRules;
use App\Models\Term;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    use TermValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('calendar.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $term = $this->route('term');

        return $term instanceof Term
            ? $this->termRules($term->schoolYear, $term)
            : [];
    }
}
