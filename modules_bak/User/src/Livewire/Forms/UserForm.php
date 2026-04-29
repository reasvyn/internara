<?php

declare(strict_types=1);

namespace Modules\User\Livewire\Forms;

use Livewire\Form;
use Modules\Permission\Enums\Role;
use Modules\Shared\Rules\Password;
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
     * Generate a random 8-character alphanumeric password.
     */
    public function generatePassword(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = substr(str_shuffle($characters), 0, 12);

        $this->password = $password;
        $this->password_confirmation = $password;
    }

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
        $this->status = $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])
            ? 'verified'
            : $user->latestStatus()?->name ?? \Modules\Status\Enums\Status::VERIFIED->value;

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
     * Get validation rules based on centralized security configuration.
     */
    public function rules(): array
    {
        $isProduction = app()->isProduction();
        $config = config('user.security');

        return [
            'name' => array_filter([
                'required',
                'string',
                $isProduction ? 'min:' . $config['name']['min_length'] : null,
                'max:' . $config['name']['max_length'],
            ]),
            'email' => array_filter([
                'required',
                $isProduction ? 'email:rfc,dns' : 'email',
                'unique:users,email,' . $this->id,
            ]),
            'username' => array_filter([
                'nullable',
                'string',
                $isProduction ? 'min:' . $config['username']['min_length'] : null,
                $isProduction ? 'max:' . $config['username']['max_length'] : 'max:50',
                $isProduction ? 'regex:' . $config['username']['pattern'] : null,
                'unique:users,username,' . $this->id,
            ]),
            'roles' => ['required', 'array', 'min:1'],
            'status' => ['required', 'string', 'in:active,inactive,pending,verified'],
            'password' => $this->id
                ? ['nullable', 'string', 'confirmed', Password::auto()]
                : ['required', 'string', 'confirmed', Password::auto()],
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
