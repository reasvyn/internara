<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\LogAuditAction;
use Spatie\Permission\Models\Role;

/**
 * S1 - Secure: Atomic role-permission sync with auditing.
 */
class UpdateRolePermissionsAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Update permissions for a given role.
     */
    public function execute(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);

        $this->logAuditAction->execute(
            action: 'role_permissions_updated',
            subjectType: Role::class,
            subjectId: (string) $role->id,
            payload: ['permissions' => $permissions],
            module: 'Permission'
        );
    }
}
