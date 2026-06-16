<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Livewire\Forms;

use Livewire\Form;

class HandbookForm extends Form
{
    public ?string $id = null;

    public string $title = '';

    public string $audience = 'all';

    public ?string $description = null;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'string', 'in:all,student,teacher,supervisor'],
            'description' => ['nullable', 'string', 'max:5000'],
            'isActive' => ['boolean'],
        ];
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'audience' => $this->audience,
            'description' => $this->description,
            'isActive' => $this->isActive,
        ];
    }
}
