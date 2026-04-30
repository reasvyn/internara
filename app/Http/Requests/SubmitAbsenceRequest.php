<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AbsenceReasonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Form Request for submitting absence request.
 * 
 * S1 - Secure: Validates absence submission at HTTP layer.
 */
class SubmitAbsenceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'reason_type' => ['required', 'string', new Enum(AbsenceReasonType::class)],
            'description' => ['required', 'string', 'max:1000'],
            'attachment' => ['sometimes', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
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
            'date.before_or_equal' => 'The absence date cannot be in the future.',
            'reason_type.in' => 'The selected reason type is invalid.',
            'attachment.mimes' => 'The attachment must be a PDF, JPG, JPEG, or PNG file.',
            'attachment.max' => 'The attachment must not exceed 2MB.',
        ];
    }
}
