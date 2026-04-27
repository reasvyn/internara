<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Illuminate\Validation\Rules\Enum;
use Livewire\Form;
use Modules\Internship\Enums\RequirementType;

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
            'type' => ['required', Enum::class => new Enum(RequirementType::class)],
            'is_mandatory' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ];
    }
}
