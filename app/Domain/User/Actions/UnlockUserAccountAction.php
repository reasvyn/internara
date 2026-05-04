<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\User\Support\HandlesActionErrors;
use Illuminate\Support\Facades\DB;

/**
 * Unlocks a previously locked user account.
 *
 * S1 - Secure: Requires admin authorization to unlock accounts.
 * S2 - Sustain: Proper error handling with atomic transactions.
 */
class UnlockUserAccountAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Unlock the given user account.
     *
     * @throws \RuntimeException when the unlock operation fails
     */
    public function execute(User $user): void
    {
        if (! $user->isLocked()) {
            return;
        }

        $this->withErrorHandling(function () use ($user) {
            DB::transaction(function () use ($user) {
                $user->unlock();

                $this->logAuditAction->execute(
                    action: 'user_account_unlocked',
                    subjectType: User::class,
                    subjectId: $user->id,
                    module: 'Auth',
                );
            });
        }, 'Failed to unlock user account');
    }
}
