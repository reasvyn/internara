declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Atomic profile updates with auditing.
 * S3 - Scalable: Stateless action.
 */
class UpdateProfileAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Execute the profile update.
     */
    public function execute(User $user, array $data): Profile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = $user->profile()->updateOrCreate(['user_id' => $user->id], $data);

            $this->logAuditAction->execute(
                action: 'profile_updated',
                subjectType: Profile::class,
                subjectId: $profile->id,
                payload: $data,
                module: 'Profile',
            );

            return $profile;
        });
    }
}
