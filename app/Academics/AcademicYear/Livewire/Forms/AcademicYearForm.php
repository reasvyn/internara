<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Livewire\Forms;

use Livewire\Form;

class AcademicYearForm extends Form
{
    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    public function rules(?string $excludeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name,'.($excludeId ?? 'NULL')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('validation.unique'),
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => false,
        ];
    }
}
