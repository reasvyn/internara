<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire\Forms;

use Livewire\Form;
use Modules\Journal\Models\JournalEntry;

class JournalForm extends Form
{
    public ?JournalEntry $entry = null;

    public ?string $id = null;
    public ?string $registration_id = null;
    public ?string $student_id = null;
    public string $date = '';
    public string $work_topic = '';
    public string $activity_description = '';
    public string $basic_competence = '';
    public string $character_values = '';
    public string $reflection = '';
    public string $mood = 'neutral';

    /**
     * Set the form values from an existing entry.
     */
    public function setEntry(JournalEntry $entry): void
    {
        $this->entry = $entry;
        $this->id = $entry->id;
        $this->registration_id = $entry->registration_id;
        $this->student_id = $entry->student_id;
        $this->date = $entry->date->format('Y-m-d');
        $this->work_topic = $entry->work_topic ?? '';
        $this->activity_description = $entry->activity_description;
        $this->basic_competence = $entry->basic_competence ?? '';
        $this->character_values = $entry->character_values ?? '';
        $this->reflection = $entry->reflection ?? '';
        $this->mood = $entry->mood;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'work_topic' => ['required', 'string', 'max:255'],
            'activity_description' => ['required', 'string'],
            'basic_competence' => ['nullable', 'string', 'max:255'],
            'character_values' => ['nullable', 'string', 'max:255'],
            'reflection' => ['nullable', 'string'],
            'mood' => ['required', 'string', 'in:happy,neutral,tired,inspired,focused'],
        ];
    }
}
