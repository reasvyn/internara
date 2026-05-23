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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
