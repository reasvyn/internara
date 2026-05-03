<?php

declare(strict_types=1);

namespace App\Domain\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'type' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'internship_id' => ['nullable', 'uuid', 'exists:internship_placements,id'],
        ];
    }
}
