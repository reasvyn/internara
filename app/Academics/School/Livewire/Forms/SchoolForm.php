<?php

declare(strict_types=1);

namespace App\Academics\School\Livewire\Forms;

use App\Academics\School\Models\School;
use Livewire\Form;

class SchoolForm extends Form
{
    public string $name = '';

    public string $institutional_code = '';

    public string $address = '';

    public string $principal_name = '';

    public string $email = '';

    public string $phone = '';

    public string $fax = '';

    public string $website = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institutional_code' => [
                'required',
                'string',
                'max:50',
            ],
            'address' => ['required', 'string', 'max:1000'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function fillFromModel(School $school): void
    {
        $this->name = $school->name;
        $this->institutional_code = $school->institutional_code;
        $this->address = $school->address ?? '';
        $this->principal_name = $school->principal_name ?? '';
        $this->email = $school->email ?? '';
        $this->phone = $school->phone ?? '';
        $this->fax = $school->fax ?? '';
        $this->website = $school->website ?? '';
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'institutional_code' => $this->institutional_code,
            'address' => $this->address,
            'principal_name' => $this->principal_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'website' => $this->website,
        ];
    }
}
