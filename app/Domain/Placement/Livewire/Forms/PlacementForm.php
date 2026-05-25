<?php

declare(strict_types=1);

namespace App\Domain\Placement\Livewire\Forms;

use Livewire\Form;

class PlacementForm extends Form
{
    public ?string $id = null;

    public string $company_id = '';

    public string $internship_id = '';

    public string $name = '';

    public string $address = '';

    public ?int $quota = null;

    public string $description = '';

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'internship_id' => ['required', 'exists:internships,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'quota' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function all(): array
    {
        return [
            'company_id' => $this->company_id,
            'internship_id' => $this->internship_id,
            'name' => $this->name,
            'address' => $this->address ?: null,
            'quota' => $this->quota,
            'description' => $this->description ?: null,
        ];
    }
}
