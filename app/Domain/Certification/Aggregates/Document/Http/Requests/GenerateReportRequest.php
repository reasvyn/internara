<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Document\Http\Requests;

use App\Domain\Certification\Aggregates\Document\Models\Document;
use App\Domain\Core\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form Request for generating report.
 *
 * S1 - Secure: Validates report generation data at HTTP layer.
 */
class GenerateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['required', 'uuid', 'exists:documents,id'],
            'registration_id' => ['required', 'uuid', 'exists:registrations,id'],
            'options' => ['sometimes', 'array'],
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
            'document_id.exists' => 'The selected document does not exist.',
            'registration_id.exists' => 'The selected registration does not exist.',
        ];
    }
}
