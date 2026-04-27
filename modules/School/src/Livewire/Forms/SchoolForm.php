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

    /**
     * Define validation rules for the school form.
     */
    public function rules(): array
    {
        $isProduction = app()->isProduction();
        $config = config('school.validation');

        return [
            'institutional_code' => array_filter([
                'required',
                'string',
                $isProduction ? 'min:'.$config['institutional_code']['min_length'] : null,
                'max:'.$config['institutional_code']['max_length'],
                $isProduction ? 'regex:'.$config['institutional_code']['pattern'] : null,
                Rule::unique('schools', 'institutional_code')->ignore($this->id),
            ]),
            'name' => array_filter([
                'required',
                'string',
                $isProduction ? 'min:'.$config['name']['min_length'] : null,
                'max:'.$config['name']['max_length'],
                Rule::unique('schools', 'name')->ignore($this->id),
            ]),
            'address' => ['nullable', 'string', 'max:1000'],
            'email' => array_filter([
                'nullable',
                $isProduction ? 'email:rfc,dns' : 'email',
                'max:255',
                Rule::unique('schools', 'email')->ignore($this->id),
            ]),
            'phone' => array_filter([
                'nullable',
                'string',
                $isProduction ? 'min:'.$config['phone']['min_length'] : null,
                'max:'.$config['phone']['max_length'],
                $isProduction ? 'regex:'.$config['phone']['pattern'] : null,
            ]),
            'fax' => array_filter([
                'nullable',
                'string',
                $isProduction ? 'min:'.$config['fax']['min_length'] : null,
                'max:'.$config['fax']['max_length'],
                $isProduction ? 'regex:'.$config['fax']['pattern'] : null,
            ]),
            'principal_name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'string'],
            'logo_file' => ['sometimes', 'nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ];
    }
}
