<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\Internship\Enums\Semester;

class InternshipForm extends Form
{
    public ?string $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $academic_year = null;

    public ?string $semester = null;

    public ?string $date_start = null;

    public ?string $date_finish = null;

    public ?string $school_id = null;

    /**
     * Define validation rules for the internship program.
     */
    public function rules(): array
    {
        $isProduction = app()->isProduction();
        $config = config('internship.validation');

        return [
            'title' => array_filter([
                'required',
                'string',
                $isProduction ? 'min:'.$config['title']['min_length'] : null,
                'max:'.$config['title']['max_length'],
                Rule::unique('internships', 'title')->ignore($this->id),
            ]),
            'description' => ['nullable', 'string'],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::enum(Semester::class)],
            'date_start' => ['required', 'date'],
            'date_finish' => ['required', 'date', 'after:date_start'],
            'school_id' => ['required', 'uuid'],
        ];
    }

    /**
     * Fill the form with record data.
     */
    public function fill($record): void
    {
        $this->id = (string) $record->id;
        $this->title = $record->title;
        $this->description = $record->description;
        $this->academic_year = $record->academic_year;
        $this->semester = $record->semester->value;
        $this->date_start = $record->date_start->toDateString();
        $this->date_finish = $record->date_finish->toDateString();
        $this->school_id = $record->school_id;
    }
}
