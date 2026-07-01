<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Livewire\Forms;

use Livewire\Form;

class LogbookForm extends Form
{
    public ?string $id = null;

    public string $user_id = '';

    public string $date = '';

    public string $content = '';

    public string $learning_outcomes = '';

    public string $status = 'draft';

    public ?string $mentor_feedback = null;

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'content' => ['required', 'string'],
            'learning_outcomes' => ['nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'date' => $this->date,
            'content' => $this->content,
            'learning_outcomes' => $this->learning_outcomes,
            'status' => $this->status,
            'mentor_feedback' => $this->mentor_feedback,
        ];
    }
}
