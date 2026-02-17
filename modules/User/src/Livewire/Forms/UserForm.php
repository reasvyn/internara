<?php

declare(strict_types=1);

namespace Modules\User\Livewire\Forms;

use Livewire\Form;
use Modules\User\Models\User;

class UserForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public array $roles = [];

    public string $status = 'active';

    /**
     * Set form values from user.
     */
    public function setUser(User $user): void
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->roles = $user->roles->pluck('name')->toArray();
        $this->status = $user->latestStatus()?->name ?? 'active';
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$this->id],
            'username' => ['nullable', 'string', 'unique:users,username,'.$this->id],
            'roles' => ['required', 'array', 'min:1'],
            'status' => ['required', 'string', 'in:active,inactive,pending'],
            'password' => $this->id
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
