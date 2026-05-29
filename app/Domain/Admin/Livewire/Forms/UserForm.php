<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Forms;

use Livewire\Form;

class UserForm extends Form
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
            'roles' => 'required|array|min:1',
        ];
    }
}
