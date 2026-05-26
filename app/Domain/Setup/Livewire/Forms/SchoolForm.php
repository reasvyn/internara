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

    public ?string $website = null;

    public ?string $principal_name = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'institutional_code' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'principal_name' => 'nullable|string|max:255',
        ];
    }
}
