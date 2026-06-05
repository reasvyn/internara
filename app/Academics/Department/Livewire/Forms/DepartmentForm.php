<?php

declare(strict_types=1);

namespace App\Academics\Department\Livewire\Forms;

use Livewire\Form;

class DepartmentForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $description = '';

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name,'.($this->id ?? 'NULL'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
