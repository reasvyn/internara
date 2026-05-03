declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;

/**
 * Locks a user account after too many failed attempts.
 *
 * S1 - Secure: Prevents brute force attacks.
 */
class LockUserAccountAction
{
    public function execute(User $user, string $reason = 'too_many_failed_attempts'): void
    {
        // TODO: Implement account lockout logic
        // Set lockout timestamp, notify user, log event
    }
}
