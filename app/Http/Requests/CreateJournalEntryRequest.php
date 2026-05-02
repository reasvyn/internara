<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Form Request for creating journal entry.
 *
 * S1 - Secure: Validates journal entry data at HTTP layer.
 */
class CreateJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', JournalEntry::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['sometimes', 'string', new Enum(JournalEntryStatus::class)],
            'registration_id' => ['required', 'uuid', 'exists:internship_registrations,id'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'The journal date cannot be in the future.',
            'registration_id.exists' => 'The selected registration does not exist.',
        ];
    }
}
