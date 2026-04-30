<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Safe user deletion with auditing.
 */
class DeleteUserAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Delete a user.
     */
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->logAuditAction->execute(
                action: 'user_deleted',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'name' => $user->name,
                    'email' => $user->email
                ],
                module: 'Auth'
            );

            $user->delete();
        });
    }
}
