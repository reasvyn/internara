<?php

declare(strict_types=1);

namespace App\Domain\Admin\Aggregates\Setup\Livewire\Forms;

use Illuminate\Validation\Rules\Password;
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
            'email' => 'required|email|max:255',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => __('setup.wizard.email_address'),
            'password' => __('setup.wizard.password'),
        ];
    }
}
