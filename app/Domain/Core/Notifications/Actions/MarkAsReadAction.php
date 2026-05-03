
declare(strict_types=1);

namespace App\Domain\Core\Notifications\Actions;

use App\Domain\Core\Notifications\Models\Notification;

/**
 * Stateless Action to mark notification as read.
 *
 * S1 - Secure: Only notification owner can mark as read.
 * S2 - Sustain: Single-purpose action.
 */
class MarkAsReadAction
{
    public function execute(Notification $notification): Notification
    {
        $notification->markAsRead();

        return $notification->fresh();
    }
}
