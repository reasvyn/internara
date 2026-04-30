<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for updating school.
 * 
 * S1 - Secure: Validates school update input at HTTP layer.
 */
class UpdateSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('school'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->route('school')?->id;

        return [
            'institutional_code' => ['sometimes', 'string', 'max:50', 'unique:schools,institutional_code,' . $schoolId],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:500'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:20'],
            'principal_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'institutional_code.unique' => 'The institutional code is already in use.',
        ];
    }
}
