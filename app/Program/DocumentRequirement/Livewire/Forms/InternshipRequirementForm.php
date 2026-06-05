<?php

declare(strict_types=1);

namespace App\Program\DocumentRequirement\Livewire\Forms;

use Livewire\Form;

class InternshipRequirementForm extends Form
{
    public ?string $id = null;

    public string $document_id = '';

    public bool $is_mandatory = true;

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'exists:documents,id'],
            'is_mandatory' => ['boolean'],
        ];
    }
}
