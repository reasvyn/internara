<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Livewire\Forms;

use Livewire\Form;

class SupervisorForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $company_id = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
            'phone' => 'nullable|string|max:20',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }
}
