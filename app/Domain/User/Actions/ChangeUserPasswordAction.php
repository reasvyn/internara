<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\User\Support\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Implements secure password change with strength validation.
 * S2 - Sustain: Proper error handling and logging.
 */
class ChangeUserPasswordAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Change user password after verifying current password.
     *
     * @throws AuthException when current password is incorrect
     * @throws RuntimeException when password change fails
     */
    public function execute(User $user, string $currentPassword, string $newPassword, ?string $confirmation = null): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            $this->logAuditAction->execute(
                action: 'password_change_failed',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['reason' => 'current_password_mismatch'],
                module: 'Auth',
            );

            throw AuthException::passwordMismatch();
        }

        $this->validateNewPassword($user, $newPassword, $confirmation);

        $this->withErrorHandling(function () use ($user, $newPassword) {
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
        }, 'Failed to change user password');
    }

    /**
     * Validate the new password meets strength requirements.
     *
     * @throws AuthException when password is too weak or same as current
     */
    protected function validateNewPassword(User $user, string $newPassword, ?string $confirmation = null): void
    {
        $data = ['password' => $newPassword];
        $rules = ['required', 'string', 'min:8'];

        if ($confirmation !== null) {
            $data['password_confirmation'] = $confirmation;
            $rules[] = 'confirmed';
        }

        Validator::make($data, ['password' => $rules])->validate();

        if (Hash::check($newPassword, $user->password)) {
            throw AuthException::invalidCredentials();
        }
    }
}
