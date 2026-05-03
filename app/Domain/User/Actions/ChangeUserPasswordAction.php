declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Exceptions\AuthException;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * S1 - Secure: Implements secure password change logic.
 * S3 - Scalable: Stateless action.
 */
class ChangeUserPasswordAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Change user password after verifying current password.
     *
     * @throws AuthException when current password is incorrect
     */
    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            $this->logAuditAction->execute(
                action: 'password_change_failed',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth',
            );

            throw AuthException::passwordMismatch();
        }

        DB::transaction(function () use ($user, $newPassword) {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            $this->logAuditAction->execute(
                action: 'password_changed',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth',
            );
        });
    }
}
