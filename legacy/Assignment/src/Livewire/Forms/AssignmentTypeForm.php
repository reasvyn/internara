<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire\Forms;

use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AssignmentTypeForm extends Form
{
    public ?string $id = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:100')]
    public string $slug = '';

    #[Validate('required|string|max:50')]
    public string $group = 'report';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    public function updatedName(string $value): void
    {
        if (! $this->id) {
            $this->slug = Str::slug($value);
        }
    }

    public function fill($record): void
    {
        $this->id = $record->id;
        $this->name = $record->name;
        $this->slug = $record->slug;
        $this->group = $record->group;
        $this->description = $record->description ?? '';
    }
}
