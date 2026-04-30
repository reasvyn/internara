<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;

/**
 * S1 - Secure: Manages removing roles securely.
 * S3 - Scalable: Stateless action.
 */
class RemoveRoleAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    public function execute(User $user, string|array $roles): void
    {
        $user->removeRole($roles);

        $this->logAuditAction->execute(
            action: 'role_removed',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['roles' => $roles],
            module: 'Permission'
        );
    }
}
