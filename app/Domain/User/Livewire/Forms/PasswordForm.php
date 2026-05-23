<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Forms;

use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class PasswordForm extends Form
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'passwordForm.current_password' => ['required', 'current_password'],
            'passwordForm.password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function resetForm(): void
    {
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
    }
}
