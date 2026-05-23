<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire\Forms;

use Livewire\Form;

class DepartmentForm extends Form
{
    public string $name = '';

    public string $description = '';

    protected function rules(): array
    {
        return [
            'departmentForm.name' => 'required|string|max:255',
            'departmentForm.description' => 'nullable|string',
        ];
    }
}
