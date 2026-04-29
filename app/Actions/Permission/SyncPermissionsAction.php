<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;

/**
 * S1 - Secure: Synchronizes user permissions securely.
 * S3 - Scalable: Stateless action.
 */
class SyncPermissionsAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(User $user, array $permissions): void
    {
        $user->syncPermissions($permissions);

        $this->logAuditAction->execute(
            action: 'permissions_synced',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['permissions' => $permissions],
            module: 'Permission'
        );
    }
}
