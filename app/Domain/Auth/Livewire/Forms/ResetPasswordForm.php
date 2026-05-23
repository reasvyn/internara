<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use Livewire\Form;

class ResetPasswordForm extends Form
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => __('auth.reset_password.email'),
            'password' => __('auth.reset_password.password'),
            'password_confirmation' => __('auth.reset_password.password_confirmation'),
        ];
    }
}
