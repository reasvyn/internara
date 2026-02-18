<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class InternshipForm extends Form
{
    public ?string $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?int $year = null;

    public ?string $semester = null;

    public ?string $date_start = null;

    public ?string $date_finish = null;

    public ?string $school_id = null;

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('internships', 'title')->ignore($this->id),
            ],
            'description' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'string', Rule::in(['Ganjil', 'Genap', 'Tahunan'])],
            'date_start' => ['required', 'date'],
            'date_finish' => ['required', 'date', 'after:date_start'],
            'school_id' => ['required', 'uuid'],
        ];
    }
}
