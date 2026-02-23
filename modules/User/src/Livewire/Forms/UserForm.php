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

    public array $profile = [
        'phone' => '',
        'address' => '',
        'department_id' => '',
        'national_identifier' => '',
        'registration_number' => '',
        'gender' => '',
        'blood_type' => '',
    ];

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

        if ($user->profile) {
            $this->profile = [
                'phone' => $user->profile->phone ?? '',
                'address' => $user->profile->address ?? '',
                'department_id' => $user->profile->department_id ?? '',
                'national_identifier' => $user->profile->national_identifier ?? '',
                'registration_number' => $user->profile->registration_number ?? '',
                'gender' => $user->profile->gender ?? '',
                'blood_type' => $user->profile->blood_type ?? '',
            ];
        }
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
                ? ['nullable', 'string', 'confirmed', \Modules\Shared\Rules\Password::auto()]
                : ['required', 'string', 'confirmed', \Modules\Shared\Rules\Password::auto()],
            'profile.phone' => ['nullable', 'string', 'max:20'],
            'profile.address' => ['nullable', 'string', 'max:500'],
            'profile.department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'profile.national_identifier' => ['nullable', 'string', 'max:50'],
            'profile.registration_number' => ['nullable', 'string', 'max:50'],
            'profile.gender' => ['nullable', 'string', 'in:male,female'],
            'profile.blood_type' => ['nullable', 'string', 'max:5'],
        ];
    }
}
