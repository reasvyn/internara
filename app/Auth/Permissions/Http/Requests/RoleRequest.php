<?php

declare(strict_types=1);

namespace App\Auth\Permissions\Http\Requests;

use App\Core\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Rule;

/**
 * Form request for role-based authorization.
 *
 * S1 - Secure: Validates role requirements.
 * S2 - Sustain: Clear validation rules.
 */
class RoleRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'roles.required' => __('validation.roles_required'),
            'roles.*.exists' => __('validation.roles_invalid', ['value' => ':value']),
        ];
    }
}
