<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\LogAuditAction;
use Spatie\Permission\Models\Role;

/**
 * S1 - Secure: Manages creation of new system roles.
 * S3 - Scalable: Stateless action.
 */
class CreateRoleAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(string $name, string $guardName = 'web'): Role
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        $this->logAuditAction->execute(
            action: 'role_created',
            subjectType: Role::class,
            subjectId: (string) $role->id,
            payload: ['name' => $name, 'guard_name' => $guardName],
            module: 'Permission'
        );

        return $role;
    }
}
