<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Livewire\Forms;

use App\Modules\Core\Support\PasswordRules;
use Livewire\Form;

class PasswordForm extends Form
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [...PasswordRules::default(), 'confirmed'],
        ];
    }

    public function resetForm(): void
    {
        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
    }
}
