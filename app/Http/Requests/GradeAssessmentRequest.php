<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for grading assessment.
 * 
 * S1 - Secure: Validates assessment grading at HTTP layer.
 */
class GradeAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('grade', \App\Models\Assessment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['sometimes', 'string', 'max:2000'],
            'competencies' => ['sometimes', 'array'],
            'competencies.*.id' => ['required', 'uuid', 'exists:competencies,id'],
            'competencies.*.score' => ['required', 'numeric', 'min:0', 'max:100'],
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
            'score.min' => 'The score must be at least 0.',
            'score.max' => 'The score must not exceed 100.',
        ];
    }
}
