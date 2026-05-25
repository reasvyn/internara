<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Livewire\Forms;

use Livewire\Form;

class HandbookForm extends Form
{
    public ?string $id = null;

    public string $title = '';

    public string $content = '';

    public string $version = '1';

    public bool $is_active = false;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
