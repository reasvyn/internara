<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Livewire\Forms;

use Livewire\Form;

class UserForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public array $roles = [];

    public string $phone = '';

    public string $address = '';

    public string $bio = '';

    public string $gender = '';

    public string $pob = '';

    public string $dob = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $emergency_contact_address = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
            'roles' => 'required|array|min:1',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'gender' => 'nullable|string|in:L,P',
            'pob' => 'nullable|string|max:100',
            'dob' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_address' => 'nullable|string|max:500',
        ];
    }
}
