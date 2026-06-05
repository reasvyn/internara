<?php

declare(strict_types=1);

namespace App\SysAdmin\Setup\Livewire\Forms;

use Livewire\Form;

class InternshipForm extends Form
{
    public string $name = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string',
        ];
    }

    public function isFilled(): bool
    {
        return $this->name !== '' || $this->start_date !== '' || $this->end_date !== '';
    }
}
