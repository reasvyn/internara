<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Livewire\Forms;

use Livewire\Form;

class PartnershipForm extends Form
{
    public ?string $id = null;

    public string $company_id = '';

    public string $agreement_number = '';

    public string $title = '';

    public string $start_date = '';

    public string $end_date = '';

    public ?string $scope = null;

    public ?string $contact_person_name = null;

    public ?string $contact_person_phone = null;

    public ?string $contact_person_email = null;

    public ?string $signed_by_school = null;

    public ?string $signed_by_company = null;

    public ?string $signed_at = null;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'agreement_number' => [
                'required',
                'string',
                'max:100',
                'unique:partnerships,agreement_number,'.($this->id ?? 'NULL'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:30'],
            'contact_person_email' => ['nullable', 'email', 'max:255'],
            'signed_by_school' => ['nullable', 'string', 'max:255'],
            'signed_by_company' => ['nullable', 'string', 'max:255'],
            'signed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'agreement_number' => $this->agreement_number,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'scope' => $this->scope,
            'contact_person_name' => $this->contact_person_name,
            'contact_person_phone' => $this->contact_person_phone,
            'contact_person_email' => $this->contact_person_email,
            'signed_by_school' => $this->signed_by_school,
            'signed_by_company' => $this->signed_by_company,
            'signed_at' => $this->signed_at,
            'notes' => $this->notes,
        ];
    }
}
