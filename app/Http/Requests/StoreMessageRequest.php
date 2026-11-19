<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to send messages.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isPortalUser() || $user->can('messages.send'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $actor = $this->user();
                $recipient = User::query()->find($this->integer('recipient_id'));

                if ($actor === null || ! $recipient instanceof User) {
                    return;
                }

                if (! $actor->canMessage($recipient)) {
                    $validator->errors()->add(
                        'recipient_id',
                        'You cannot send a message to this person.',
                    );
                }
            },
        ];
    }
}
