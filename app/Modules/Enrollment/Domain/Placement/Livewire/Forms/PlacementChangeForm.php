<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Livewire\Forms;

use Livewire\Form;

class PlacementChangeForm extends Form
{
    public string $registration_id = '';

    public string $to_placement_id = '';

    public string $reason = '';

    public function rules(): array
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'to_placement_id' => ['required', 'exists:placements,id'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
