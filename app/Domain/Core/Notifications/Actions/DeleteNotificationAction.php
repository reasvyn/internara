
declare(strict_types=1);

namespace App\Domain\Core\Notifications\Actions;

use App\Domain\Core\Notifications\Models\Notification;

/**
 * Stateless Action to delete a notification.
 *
 * S1 - Secure: Only owner can delete.
 * S2 - Sustain: Clean removal.
 */
class DeleteNotificationAction
{
    public function execute(Notification $notification): void
    {
        $notification->delete();
    }
}
