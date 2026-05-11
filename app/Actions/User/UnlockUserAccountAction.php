<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use App\Support\User\HandlesActionErrors;
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
        if ($user->locked_at === null) {
            return;
        }

        $this->withErrorHandling(function () use ($user) {
            DB::transaction(function () use ($user) {
                $user->update([
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);

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
