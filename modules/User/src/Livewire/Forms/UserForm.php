<?php

declare(strict_types=1);

namespace Modules\User\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $password = '';

    public array $roles = [];

    public string $status = 'active';

    public array $profile = [];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->id)],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($this->id),
            ],
            'password' => [$this->id ? 'nullable' : 'required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'profile' => ['nullable', 'array'],
            'profile.identity_number' => ['nullable', 'string'], // NISN or NIP
            'profile.department_id' => ['nullable', 'exists:departments,id'],
            'profile.phone' => ['nullable', 'string'],
        ];
    }
}
