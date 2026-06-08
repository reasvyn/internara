<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Livewire\Forms;

use Livewire\Form;

class DirectPlacementForm extends Form
{
    public string $student_id = '';

    public string $placement_id = '';

    public string $academic_year = '';

    public array $mentor_ids = [];

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:users,id'],
            'placement_id' => ['required', 'exists:placements,id'],
            'academic_year' => ['required'],
            'mentor_ids' => ['nullable', 'array'],
            'mentor_ids.*' => ['exists:mentors,id'],
        ];
    }
}
