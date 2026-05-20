<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Http\Requests;

use App\Domain\Core\Http\Requests\FormRequest;

class CreateHandbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
