<?php

declare(strict_types=1);

namespace Modules\Department\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class DepartmentForm extends Form
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $description = null;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($this->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
