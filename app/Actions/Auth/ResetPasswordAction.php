<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * S1 - Secure: Implements secure password reset logic.
 * S3 - Scalable: Stateless action.
 */
class ResetPasswordAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Reset the user's password.
     */
    public function execute(string $email, string $token, string $password, string $passwordConfirmation): bool
    {
        $credentials = [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ];

        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            $this->logAuditAction->execute(
                action: 'password_reset_success',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth'
            );
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->logAuditAction->execute(
                action: 'password_reset_failed',
                payload: ['email' => $email],
                module: 'Auth'
            );
        }

        return $status === Password::PASSWORD_RESET;
    }
}
