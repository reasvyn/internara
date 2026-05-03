
declare(strict_types=1);

namespace App\Domain\Core\Notifications\Actions;

use App\Domain\User\Models\User;
use App\Domain\Core\Notifications\Models\Notification;

/**
 * Stateless Action to send in-app notification.
 *
 * S1 - Secure: Validates user exists.
 * S2 - Sustain: Single-purpose action.
 */
class SendNotificationAction
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): Notification {
        $user = User::findOrFail($userId);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
            'is_read' => false,
        ]);

        return $notification;
    }
}
