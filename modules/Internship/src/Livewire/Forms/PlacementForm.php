<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Livewire\Form;

class PlacementForm extends Form
{
    public ?string $id = null;

    public ?string $internship_id = null;

    public ?string $company_id = null;

    public ?string $mentor_id = null;

    public int $capacity_quota = 1;

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'internship_id' => ['required', 'uuid'],
            'company_id' => ['required', 'uuid'],
            'mentor_id' => ['nullable', 'uuid'],
            'capacity_quota' => ['required', 'integer', 'min:1'],
        ];
    }
}
