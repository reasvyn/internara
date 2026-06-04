<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Livewire\Forms;

use Livewire\Form;

class AdminUserForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public array $roles = [];

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->id ?? 'NULL'),
        ];
    }
}
