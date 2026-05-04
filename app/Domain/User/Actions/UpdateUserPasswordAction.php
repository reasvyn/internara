<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Implements secure password update with strength validation.
 * S2 - Sustain: Proper error handling and logging.
 */
class UpdateUserPasswordAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Update the user's password (admin override).
     *
     * @throws RuntimeException when update fails
     */
    public function execute(User $user, string $newPassword): void
    {
        $this->validateNewPassword($newPassword);

        try {
            DB::transaction(function () use ($user, $newPassword) {
                $user->update([
                    'password' => Hash::make($newPassword),
                ]);

                $this->logAuditAction->execute(
                    action: 'password_updated_manually',
                    subjectType: User::class,
                    subjectId: $user->id,
                    module: 'Auth',
                );
            });
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to update user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to update password.', 0, $e);
        }
    }

    /**
     * Validate password meets minimum strength requirements.
     */
    protected function validateNewPassword(string $newPassword): void
    {
        Validator::make([
            'password' => $newPassword,
        ], [
            'password' => ['required', 'string', 'min:8'],
        ])->validate();
    }
}
