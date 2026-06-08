<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Livewire\Forms;

use Livewire\Form;

class TeacherForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $employee_id_number = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
        ];
    }
}
