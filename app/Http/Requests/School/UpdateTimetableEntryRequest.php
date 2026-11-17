<?php

namespace App\Http\Requests\School;

use App\Concerns\TimetableEntryRules;
use App\Models\TimetableEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTimetableEntryRequest extends FormRequest
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
        $entry = $this->route('entry');

        return $entry instanceof TimetableEntry
            ? $this->entryRules($entry->courseClass, $entry)
            : [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $entry = $this->route('entry');

            if ($entry instanceof TimetableEntry) {
                $this->validateTeacherAvailability($validator, $entry->courseClass, $entry);
            }
        });
    }
}
