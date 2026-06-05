<?php

declare(strict_types=1);

namespace App\Enrollment\Livewire\Forms;

use App\Program\Internship\Rules\OpenForRegistration;
use Livewire\Form;

class RegistrationWizardForm extends Form
{
    public string $internship_id = '';

    public string $placement_id = '';

    public string $academic_year = '';

    public string $proposed_company_name = '';

    public string $proposed_company_address = '';

    public function rules(): array
    {
        return [
            'internship_id' => ['required', new OpenForRegistration],
            'academic_year' => ['required'],
        ];
    }
}
