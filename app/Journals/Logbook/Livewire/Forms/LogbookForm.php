<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Livewire\Forms;

use Livewire\Form;

class LogbookForm extends Form
{
    public ?string $id = null;

    public string $userId = '';

    public string $date = '';

    public string $content = '';

    public string $learningOutcomes = '';

    public string $status = 'draft';

    public ?string $mentorFeedback = null;

    public function rules(): array
    {
        return [
            'userId' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'content' => ['required', 'string'],
            'learningOutcomes' => ['nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'date' => $this->date,
            'content' => $this->content,
            'learning_outcomes' => $this->learningOutcomes,
            'status' => $this->status,
            'mentor_feedback' => $this->mentorFeedback,
        ];
    }
}
