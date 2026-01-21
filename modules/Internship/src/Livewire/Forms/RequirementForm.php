<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Livewire\Form;

class RequirementForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public ?string $description = null;

    public string $type = 'document';

    public bool $is_mandatory = true;

    public bool $is_active = true;

    public string $academic_year = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [
                'required',
                \Illuminate\Validation\Rules\Enum::class => new \Illuminate\Validation\Rules\Enum(
                    \Modules\Internship\Enums\RequirementType::class,
                ),
            ],
            'is_mandatory' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ];
    }
}
