<?php

declare(strict_types=1);

namespace App\Partners\Company\Livewire\Forms;

use Livewire\Form;

class CompanyForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $address = '';

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $website = null;

    public ?string $description = null;

    public ?string $industry_sector = null;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:companies,name,'.($this->id ?? 'NULL'),
            ],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'industry_sector' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'description' => $this->description,
            'industry_sector' => $this->industry_sector,
        ];
    }
}
