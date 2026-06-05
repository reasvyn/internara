<?php

declare(strict_types=1);

namespace App\Program\Internship\Livewire\Forms;

use App\Program\Internship\Enums\InternshipStatus;
use Livewire\Form;

class InternshipForm extends Form
{
    public ?string $id = null;

    public string $name = '';

    public string $academic_year_id = '';

    public string $start_date = '';

    public string $end_date = '';

    public ?string $registration_start_date = null;

    public ?string $registration_end_date = null;

    public string $description = '';

    public string $status = 'draft';

    public function rules(): array
    {
        $validStatuses = collect(InternshipStatus::cases())->map(fn ($s) => $s->value)->implode(',');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:internships,name,'.($this->id ?? 'NULL')],
            'academic_year_id' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'registration_start_date' => ['nullable', 'date'],
            'registration_end_date' => ['nullable', 'date', 'after_or_equal:registration_start_date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.$validStatuses],
        ];
    }
}
