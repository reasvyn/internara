<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Livewire\Forms;

use Livewire\Form;

class StudentForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $national_id_number = '';

    public string $student_id_number = '';

    public string $department_id = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
            'national_id_number' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
        ];
    }
}
