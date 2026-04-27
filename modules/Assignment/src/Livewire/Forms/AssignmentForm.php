<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AssignmentForm extends Form
{
    public ?string $id = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string')]
    public string $assignment_type_id = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('boolean')]
    public bool $is_mandatory = true;

    #[Validate('nullable|date')]
    public $due_date;

    public function fill($record): void
    {
        $this->id = $record->id;
        $this->title = $record->title;
        $this->assignment_type_id = $record->assignment_type_id;
        $this->description = $record->description ?? '';
        $this->is_mandatory = (bool) $record->is_mandatory;
        $this->due_date = $record->due_date?->format('Y-m-d\TH:i');
    }
}
