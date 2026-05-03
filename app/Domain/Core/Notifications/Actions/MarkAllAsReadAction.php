
declare(strict_types=1);

namespace App\Domain\Core\Notifications\Actions;

use App\Domain\User\Models\User;

/**
 * Stateless Action to mark all user's notifications as read.
 *
 * S1 - Secure: Only user can mark their own notifications.
 * S2 - Sustain: Batch operation.
 */
class MarkAllAsReadAction
{
    public function execute(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
