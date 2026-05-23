<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire\Forms;

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
            'internshipForm.name' => 'required|string|max:255',
            'internshipForm.start_date' => 'required|date',
            'internshipForm.end_date' => 'required|date|after:internshipForm.start_date',
            'internshipForm.description' => 'nullable|string',
        ];
    }

    public function isFilled(): bool
    {
        return $this->name !== '' || $this->start_date !== '' || $this->end_date !== '';
    }
}
