<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\User\Support\HandlesActionErrors;
use Illuminate\Support\Facades\DB;

/**
 * Locks a user account after too many failed attempts.
 *
 * S1 - Secure: Prevents brute force attacks by locking accounts.
 * S2 - Sustain: Proper error handling with atomic transactions.
 */
class LockUserAccountAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Lock the given user account.
     *
     * @throws \RuntimeException when the lock operation fails
     */
    public function execute(User $user, string $reason = 'too_many_failed_attempts'): void
    {
        if ($user->isLocked()) {
            return;
        }

        $this->withErrorHandling(function () use ($user, $reason) {
            DB::transaction(function () use ($user, $reason) {
                $user->lock($reason);

                $this->logAuditAction->execute(
                    action: 'user_account_locked',
                    subjectType: User::class,
                    subjectId: $user->id,
                    payload: ['reason' => $reason],
                    module: 'Auth',
                );
            });
        }, 'Failed to lock user account');
    }
}
