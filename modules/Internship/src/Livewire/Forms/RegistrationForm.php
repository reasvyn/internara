<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Livewire\Form;

class RegistrationForm extends Form
{
    public ?string $id = null;

    public ?string $internship_id = null;

    public ?string $placement_id = null;

    public ?string $student_id = null;

    public function rules(): array
    {
        return [
            'internship_id' => ['required', 'uuid'],
            'placement_id' => ['required', 'uuid'],
            'student_id' => ['required', 'uuid'],
        ];
    }
}
