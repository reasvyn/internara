<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;

/**
 * S1 - Secure: Manages assigning roles securely.
 * S3 - Scalable: Stateless action.
 */
class AssignRoleAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    public function execute(User $user, string|array $roles): void
    {
        $user->assignRole($roles);

        $this->logAuditAction->execute(
            action: 'role_assigned',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['roles' => $roles],
            module: 'Permission'
        );
    }
}
