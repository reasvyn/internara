<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Safely deletes a user with pre-deletion guards and audit logging.
 *
 * S1 - Secure: Prevents self-deletion and last admin deletion.
 */
class DeleteUserAction
{
    /**
     * Create a new action instance.
     */
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Delete a user after running safety checks.
     *
     * @throws RuntimeException when trying to delete self or last admin
     */
    public function execute(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin') && $this->isLastSuperAdmin($user)) {
            throw new RuntimeException('Cannot delete the last administrator account.');
        }

        DB::transaction(function () use ($user) {
            $this->logAuditAction->execute(
                action: 'user_deleted',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                module: 'Auth',
            );

            $user->delete();
        });
    }

    /**
     * Check if the user is the last super admin.
     */
    protected function isLastSuperAdmin(User $user): bool
    {
        $superAdminCount = User::role('super_admin')->count();

        return $superAdminCount <= 1 && $user->hasRole('super_admin');
    }
}
