<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire\Forms;

use Livewire\Form;

class ForgotPasswordForm extends Form
{
    public string $email = '';

    protected function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'email' => __('auth.forgot_password.email'),
        ];
    }
}
