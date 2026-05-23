<?php

declare(strict_types=1);

namespace App\Domain\Setup\Livewire\Forms;

use Livewire\Form;

class AdminForm extends Form
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'adminForm.email' => 'required|email|max:255',
            'adminForm.password' => 'required|string|min:8|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/|confirmed',
        ];
    }
}
