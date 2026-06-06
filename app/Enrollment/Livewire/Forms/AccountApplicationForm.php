<?php

declare(strict_types=1);

namespace App\Enrollment\Livewire\Forms;

use App\Program\Internship\Rules\OpenForRegistration;
use Livewire\Form;

class AccountApplicationForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $national_id_number = '';

    public string $student_id_number = '';

    public ?string $department_id = null;

    public string $class_name = '';

    public ?string $entry_year = null;

    public string $internship_id = '';

    public ?string $placement_id = null;

    public string $academic_year = '';

    public ?string $proposed_company_name = null;

    public ?string $proposed_company_address = null;

    public bool $use_placement = true;

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:account_applications,email', 'unique:users,email'],
            'internship_id' => ['required', 'exists:internships,id', new OpenForRegistration],
            'academic_year' => ['required', 'string', 'max:20'],
        ];

        if ($this->use_placement) {
            $rules['placement_id'] = ['required', 'exists:placements,id'];
        } else {
            $rules['proposed_company_name'] = ['required', 'string', 'max:255'];
            $rules['proposed_company_address'] = ['required', 'string', 'max:1000'];
        }

        return $rules;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'national_id_number' => $this->national_id_number,
            'student_id_number' => $this->student_id_number,
            'department_id' => $this->department_id ?: null,
            'class_name' => $this->class_name,
            'entry_year' => $this->entry_year ? (int) $this->entry_year : null,
            'internship_id' => $this->internship_id,
            'placement_id' => $this->use_placement ? $this->placement_id : null,
            'academic_year' => $this->academic_year,
            'proposed_company_name' => $this->use_placement ? null : $this->proposed_company_name,
            'proposed_company_address' => $this->use_placement ? null : $this->proposed_company_address,
        ];
    }
}
