<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Livewire\Forms;

use App\Modules\Core\Support\PasswordRules;
use Livewire\Form;

class PasswordForm extends Form
{
    public ?string $current_password = null;

    public ?string $password = null;

    public ?string $password_confirmation = null;

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [...PasswordRules::default(), 'confirmed'],
        ];
    }

    public function resetForm(): void
    {
        $this->current_password = null;
        $this->password = null;
        $this->password_confirmation = null;
    }
}
