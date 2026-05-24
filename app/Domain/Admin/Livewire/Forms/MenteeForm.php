<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Forms;

use Livewire\Form;

class MenteeForm extends Form
{
    public ?string $id = null;

    public ?string $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $internal_notes = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->editingUserId ?? 'NULL'),
        ];
    }
}
