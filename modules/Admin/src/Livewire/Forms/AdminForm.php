<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Forms;

use Livewire\Form;
use Modules\Shared\Rules\Password;
use Modules\User\Models\User;

/**
 * Class AdminForm
 *
 * Specialized form for managing administrative accounts.
 */
class AdminForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public array $roles = ['admin'];

    public array $profile = [
        'phone' => '',
        'address' => '',
        'gender' => '',
    ];

    public string $status = 'active';

    public function fillFromUser(User $user): void
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->status = $user->latestStatus()?->name ?? User::STATUS_ACTIVE;
        $this->password = '';
        $this->password_confirmation = '';
        $this->profile = [
            'phone' => $user->profile?->phone ?? '',
            'address' => $user->profile?->address ?? '',
            'gender' => $user->profile?->gender ?? '',
        ];
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
            'status' => ['required', 'string', 'in:active,inactive,pending'],
            'password' => $this->id
                ? ['nullable', 'string', 'confirmed', Password::auto()]
                : ['required', 'string', 'confirmed', Password::auto()],
            'profile.phone' => ['nullable', 'string', 'max:20'],
            'profile.address' => ['nullable', 'string', 'max:500'],
            'profile.gender' => ['nullable', 'string', 'in:male,female'],
        ];
    }
}
