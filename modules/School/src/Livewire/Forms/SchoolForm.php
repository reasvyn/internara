<?php

declare(strict_types=1);

namespace Modules\School\Livewire\Forms;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SchoolForm extends Form
{
    public ?string $id = null;

    public ?string $institutional_code = null;

    public ?string $name = null;

    public ?string $address = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $fax = null;

    public ?string $principal_name = null;

    public ?string $logo_url = null;

    public ?UploadedFile $logo_file = null;

    public function rules(): array
    {
        return [
            'institutional_code' => [
                'required',
                'string',
                'min:3',
                Rule::unique('schools', 'institutional_code')->ignore($this->id),
            ],
            'name' => ['required', 'string', Rule::unique('schools', 'name')->ignore($this->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('schools', 'email')->ignore($this->id),
            ],
            'phone' => ['nullable', 'string', 'min:8', 'max:15'],
            'fax' => ['nullable', 'string', 'min:8', 'max:15'],
            'principal_name' => ['nullable', 'string'],
            'logo_url' => ['sometimes', 'nullable', 'string'],
            'logo_file' => ['sometimes', 'nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ];
    }
}
