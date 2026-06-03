<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Logbook\Http\Requests;

use App\Domain\Core\Http\Requests\FormRequest;
use App\Domain\Journals\Aggregates\Logbook\Models\Logbook;
use Illuminate\Contracts\Validation\ValidationRule;

class CreateLogbookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Logbook::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['sometimes', 'string'],
            'registration_id' => ['required', 'uuid', 'exists:registrations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'The journal date cannot be in the future.',
            'registration_id.exists' => 'The selected registration does not exist.',
        ];
    }
}
