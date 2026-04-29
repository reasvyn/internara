<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class HandbookForm extends Form
{
    public ?string $id = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|string|max:20')]
    public string $version = '1.0';

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('boolean')]
    public bool $is_mandatory = true;

    public $file;

    public function fill($record): void
    {
        $this->id = $record->id;
        $this->title = $record->title;
        $this->description = $record->description ?? '';
        $this->version = $record->version;
        $this->is_active = (bool) $record->is_active;
        $this->is_mandatory = (bool) $record->is_mandatory;
    }
}
