<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Safe user deletion with auditing.
 */
class DeleteUserAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Delete a user.
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
