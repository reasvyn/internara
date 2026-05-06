<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Core\LogAuditAction;
use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * @throws AuthException when trying to delete self or last admin
     */
    public function execute(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw AuthException::cannotDeleteSelf();
        }

        if ($user->hasRole('super_admin') && $this->isLastSuperAdmin($user)) {
            throw AuthException::cannotDeleteLastAdmin();
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
