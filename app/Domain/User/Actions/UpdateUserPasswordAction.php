declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * S1 - Secure: Implements secure password update logic.
 */
class UpdateUserPasswordAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Update the user's password.
     */
    public function execute(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->logAuditAction->execute(
            action: 'password_updated_manually',
            subjectType: User::class,
            subjectId: $user->id,
            module: 'Auth',
        );
    }
}
