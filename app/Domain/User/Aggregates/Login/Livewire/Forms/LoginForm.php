<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\Login\Livewire\Forms;

use Livewire\Form;

class LoginForm extends Form
{
    public string $identifier = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'identifier' => 'required|string',
            'password' => 'required|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'identifier' => __('auth.login.identifier'),
            'password' => __('auth.login.password'),
        ];
    }
}
