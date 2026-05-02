<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class CompanyForm extends Form
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $address = null;

    public ?string $business_field = null;

    public ?string $phone = null;

    public ?string $fax = null;

    public ?string $email = null;

    public ?string $leader_name = null;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('internship_companies', 'name')->ignore($this->id),
            ],
            'address' => ['nullable', 'string'],
            'business_field' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'leader_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
