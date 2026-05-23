<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire\Forms;

use Livewire\Form;

class SchoolForm extends Form
{
    public string $name = '';

    public string $institutional_code = '';

    public string $address = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $principal_name = '';

    protected function rules(): array
    {
        return [
            'schoolForm.name' => 'required|string|max:255',
            'schoolForm.institutional_code' => 'required|string|max:50',
            'schoolForm.email' => 'required|email|max:255',
            'schoolForm.address' => 'nullable|string',
            'schoolForm.phone' => 'nullable|string|max:20',
            'schoolForm.website' => 'nullable|url|max:255',
            'schoolForm.principal_name' => 'nullable|string|max:255',
        ];
    }
}
