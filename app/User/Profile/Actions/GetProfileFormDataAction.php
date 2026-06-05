<?php

declare(strict_types=1);

namespace App\User\Profile\Actions;

use App\Core\Actions\BaseAction;
use App\User\Models\User;

final class GetProfileFormDataAction extends BaseAction
{
    private const ROLE_STAFF = ['super_admin', 'admin', 'teacher'];

    private const ROLE_STUDENT = ['student'];

    private const ROLE_SUPERVISOR = ['supervisor'];

    /** @return array{fields: string[], staffFields: string[], canChangeName: bool, canChangeUsername: bool, role: string} */
    public function execute(User $user): array
    {
        $role = $user->getRoleNames()->first() ?? 'unknown';
        $isStaff = $user->hasAnyRole(self::ROLE_STAFF);
        $isSuperAdmin = $user->hasRole('super_admin');

        $fields = ['name', 'email', 'phone', 'address', 'bio'];

        $staffFields = [];
        if ($isStaff) {
            $staffFields = [
                'employment_status',
                'job_title',
                'employee_id_number',
                'educator_id_number',
                'competence_field',
            ];
        }

        return [
            'fields' => $fields,
            'staffFields' => $staffFields,
            'canChangeName' => ! $isSuperAdmin,
            'canChangeUsername' => ! $isSuperAdmin,
            'role' => $role,
        ];
    }
}
