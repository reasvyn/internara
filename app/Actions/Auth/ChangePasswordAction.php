<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * S1 - Secure: Implements secure password change logic.
 * S3 - Scalable: Stateless action.
 */
class ChangePasswordAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            $this->logAuditAction->execute(
                action: 'password_change_failed',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth'
            );

            abort(422, __('auth.password_mismatch'));
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->logAuditAction->execute(
            action: 'password_changed',
            subjectType: User::class,
            subjectId: $user->id,
            module: 'Auth'
        );
    }
}
