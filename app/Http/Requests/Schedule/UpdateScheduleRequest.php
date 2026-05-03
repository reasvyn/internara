<?php

declare(strict_types=1);

namespace App\Domain\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'type' => ['sometimes', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'internship_id' => ['nullable', 'uuid', 'exists:internship_placements,id'],
        ];
    }
}
