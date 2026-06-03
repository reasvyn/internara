<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipPhase\Livewire\Forms;

use Livewire\Form;

class InternshipPhaseForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $color = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
