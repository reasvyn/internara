<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Submission\Http\Requests;

use App\Domain\Core\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form Request for submitting assignment.
 *
 * S1 - Secure: Validates assignment submission at HTTP layer.
 */
class SubmitAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
            'file' => ['sometimes', 'file', 'mimes:pdf,doc,docx,zip', 'max:5120'],
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
            'file.mimes' => 'The file must be a PDF, DOC, DOCX, or ZIP file.',
            'file.max' => 'The file must not exceed 5MB.',
        ];
    }
}
