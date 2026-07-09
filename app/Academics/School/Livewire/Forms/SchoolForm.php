<?php

declare(strict_types=1);

namespace App\Academics\School\Livewire\Forms;

use App\Academics\School\Entities\SchoolEntity;
use Livewire\Form;

class SchoolForm extends Form
{
    public string $name = '';

    public string $institutional_code = '';

    public string $email = '';

    public string $phone = '';

    public string $fax = '';

    public string $address = '';

    public string $website = '';

    public string $principal_name = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institutional_code' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function loadFromEntity(): void
    {
        $entity = SchoolEntity::get();

        $this->name = $entity->name();
        $this->institutional_code = $entity->institutionalCode();
        $this->email = $entity->email();
        $this->phone = $entity->phone();
        $this->address = $entity->address();
        $this->website = $entity->website();
        $this->principal_name = $entity->principalName();
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'institutional_code' => $this->institutional_code,
            'email' => $this->email,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'address' => $this->address,
            'website' => $this->website,
            'principal_name' => $this->principal_name,
        ];
    }
}
