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

    public ?string $teacher_id = null;

    public ?string $mentor_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function rules(): array
    {
        return [
            'internship_id' => ['required', 'uuid'],
            'placement_id' => ['nullable', 'uuid', 'required_without:proposed_company_name'],
            'student_id' => ['required', 'uuid'],
            'teacher_id' => ['required', 'uuid'],
            'mentor_id' => ['nullable', 'uuid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'proposed_company_name' => ['nullable', 'string', 'max:255'],
            'proposed_company_address' => ['nullable', 'string'],
        ];
    }
}
