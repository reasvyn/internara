
declare(strict_types=1);

namespace App\Domain\Core\Notifications\Actions;

use App\Domain\User\Models\User;
use App\Domain\Core\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Stateless Action to get user's notifications.
 *
 * S2 - Sustain: Filtered retrieval with pagination support.
 */
class GetNotificationsAction
{
    public function execute(string $userId, bool $unreadOnly = false, int $limit = 20): Collection
    {
        $query = Notification::where('user_id', $userId);

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
