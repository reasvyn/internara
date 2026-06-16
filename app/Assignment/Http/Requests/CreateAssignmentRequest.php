<?php

declare(strict_types=1);

namespace App\Assignment\Http\Requests;

use App\Assignment\Models\Assignment;
use App\Core\Http\Requests\BaseFormRequest;

class CreateAssignmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Assignment::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'assignment_type' => ['required', 'string', 'in:project,report,essay'],
            'due_date' => ['required', 'date', 'after:today'],
            'max_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'internship_id' => ['required', 'uuid', 'exists:internships,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after' => 'The due date must be in the future.',
            'internship_id.exists' => 'The selected internship does not exist.',
        ];
    }
}
