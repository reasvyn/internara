<?php

declare(strict_types=1);

namespace App\Actions\AccountLifecycle;

use App\Models\User;

/**
 * Unlocks a previously locked user account.
 *
 * S1 - Secure: Requires admin authorization to unlock accounts.
 */
class UnlockAccountAction
{
    public function execute(User $user): void
    {
        // TODO: Implement account unlock logic
        // Clear lockout timestamp, notify user, log event
    }
}
