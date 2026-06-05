<?php

declare(strict_types=1);

namespace App\User\AccountRecovery\Livewire\Forms;

use Livewire\Form;

class AccountRecoveryForm extends Form
{
    public string $username = '';

    public string $recoveryCode = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'username' => 'required|string',
            'recoveryCode' => 'required|string|size:12',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'username' => __('auth.account_recovery.username'),
            'recoveryCode' => __('auth.account_recovery.recovery_code'),
            'password' => __('auth.account_recovery.new_password'),
            'password_confirmation' => __('auth.account_recovery.confirm_password'),
        ];
    }
}
