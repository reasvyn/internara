<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire\Forms;

use Livewire\Form;

class InternshipGroupForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $internship_id = '';

    public ?string $placement_id = null;

    public string $description = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'internship_id' => ['required', 'exists:internships,id'],
            'placement_id' => ['nullable', 'exists:placements,id'],
        ];
    }

    public function all(): array
    {
        return [
            'name' => $this->name,
            'internship_id' => $this->internship_id,
            'placement_id' => $this->placement_id ?: null,
            'description' => $this->description,
        ];
    }
}
