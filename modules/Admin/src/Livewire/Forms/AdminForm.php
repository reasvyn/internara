<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Forms;

use Livewire\Form;

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
    ];

    public string $status = 'active';

    /**
     * Fill the form with data from an array.
     */
    public function fillData(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->username = $data['username'] ?? '';
        $this->status = $data['status'] ?? 'active';

        if (isset($data['profile'])) {
            $this->profile = [
                'phone' => $data['profile']['phone'] ?? '',
                'address' => $data['profile']['address'] ?? '',
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
            'email' => ['required', 'email'],
            'username' => ['nullable', 'string'],
            'roles' => ['required', 'array', 'min:1'],
            'status' => ['required', 'string', 'in:active,inactive,pending'],
            'password' => $this->id
                ? ['nullable', 'string', 'confirmed']
                : ['required', 'string', 'confirmed'],
            'profile.phone' => ['nullable', 'string', 'max:20'],
            'profile.address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
