<?php

declare(strict_types=1);

namespace Modules\Department\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class DepartmentForm extends Form
{
    public ?string $id = null;

    public ?string $name = null;

    public ?string $description = null;

    /**
     * Define validation rules for the department form.
     */
    public function rules(): array
    {
        $isProduction = app()->isProduction();
        $config = config('department.validation');

        return [
            'name' => array_filter([
                'required',
                'string',
                $isProduction ? 'min:' . $config['name']['min_length'] : null,
                'max:' . $config['name']['max_length'],
                Rule::unique('departments', 'name')->ignore($this->id),
            ]),
            'description' => ['nullable', 'string', 'max:' . $config['description']['max_length']],
        ];
    }
}
