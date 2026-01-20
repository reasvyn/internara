<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Livewire\Form;

class PlacementForm extends Form
{
    public ?string $id = null;

    public ?string $internship_id = null;

    public ?string $company_name = null;

    public ?string $company_address = null;

    public ?string $contact_person = null;

    public ?string $contact_number = null;

    public ?string $mentor_id = null;

    public int $slots = 1;

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'internship_id' => ['required', 'uuid'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'mentor_id' => ['nullable', 'uuid'],
            'slots' => ['required', 'integer', 'min:1'],
        ];
    }
}
