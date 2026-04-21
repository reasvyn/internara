<?php

declare(strict_types=1);

namespace Modules\User\Livewire\Forms;

use Modules\Permission\Enums\Role;
use Modules\Shared\Rules\Password;

class UserManagerForm extends UserForm
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['password'] = ['nullable', 'string', 'confirmed', Password::auto()];
        $rules['roles.*'] = ['required', 'string', 'in:'.implode(',', [
            Role::STUDENT->value,
            Role::TEACHER->value,
            Role::MENTOR->value,
        ])];

        $isPrivilegedContext = in_array(Role::ADMIN->value, $this->roles, true)
            || in_array(Role::SUPER_ADMIN->value, $this->roles, true);

        $rules['status'] = [
            'required',
            'string',
            'in:'.implode(',', $isPrivilegedContext
                ? ['verified']
                : ['active', 'inactive', 'pending']),
        ];

        return $rules;
    }
}
