<?php

declare(strict_types=1);

namespace App\Auth\Password\Livewire\Forms;

use Livewire\Form;

class ConfirmPasswordForm extends Form
{
    public string $password = '';

    protected function rules(): array
    {
        return [
            'password' => 'required|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'password' => __('auth.confirm_password.password'),
        ];
    }
}
