<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire\Forms;

use Livewire\Form;

class BriefingForm extends Form
{
    public ?string $id = null;

    public string $title = '';

    public string $description = '';

    public string $date = '';

    public string $location = '';

    public bool $is_mandatory = true;

    public string $internship_id = '';

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_mandatory' => ['boolean'],
            'internship_id' => ['required', 'exists:internships,id'],
        ];
    }
}
