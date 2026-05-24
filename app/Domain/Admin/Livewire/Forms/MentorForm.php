<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Forms;

use App\Domain\Mentor\Models\Mentor;
use Livewire\Form;

class MentorForm extends Form
{
    public ?string $id = null;

    public ?string $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $type = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->editingUserId ?? 'NULL'),
            'type' => 'required|string|in:'.Mentor::TYPE_SCHOOL_TEACHER.','.Mentor::TYPE_INDUSTRY_SUPERVISOR,
        ];
    }
}
