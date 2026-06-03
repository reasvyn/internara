<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Livewire\Forms;

use Livewire\Form;

class PlacementChangeForm extends Form
{
    public string $to_placement_id = '';

    public string $reason = '';

    public function rules(): array
    {
        return [
            'to_placement_id' => ['required', 'exists:placements,id'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }
}
