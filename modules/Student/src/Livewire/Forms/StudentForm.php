<?php

declare(strict_types=1);

namespace Modules\Student\Livewire\Forms;

use Livewire\Form;
use Modules\Shared\Rules\Password;
use Modules\User\Models\User;

class StudentForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = User::STATUS_ACTIVE;

    /**
     * @var array<string, string>
     */
    public array $profile = [
        'phone' => '',
        'address' => '',
        'department_id' => '',
        'national_identifier' => '',
        'registration_number' => '',
        'gender' => '',
        'blood_type' => '',
    ];

    public function generatePassword(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = substr(str_shuffle($characters), 0, 12);

        $this->password = $password;
        $this->password_confirmation = $password;
    }

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
            'department_id' => $user->profile?->department_id ?? '',
            'national_identifier' => $user->profile?->national_identifier ?? '',
            'registration_number' => $user->profile?->registration_number ?? '',
            'gender' => $user->profile?->gender ?? '',
            'blood_type' => $user->profile?->blood_type ?? '',
        ];
    }

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
            'profile.department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'profile.national_identifier' => ['nullable', 'string', 'max:50'],
            'profile.registration_number' => ['nullable', 'string', 'max:50'],
            'profile.gender' => ['nullable', 'string', 'in:male,female'],
            'profile.blood_type' => ['nullable', 'string', 'max:5'],
        ];
    }
}
