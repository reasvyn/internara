<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Auth\Password\Events\PasswordUpdated;
use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Support\PasswordRules;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class UpdateUserPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $newPassword): ActionResponse
    {
        $this->validateNewPassword($newPassword);

        $this->withErrorHandling(function () use ($user, $newPassword) {
            $this->transaction(function () use ($user, $newPassword) {
                $user->update([
                    'password' => Hash::make($newPassword),
                ]);

                $this->dispatchEvent(new PasswordUpdated($user));
                $this->log('password_updated_manually', $user);
            });
        }, 'Failed to update user password');

        return ActionResponse::ok();
    }

    protected function validateNewPassword(string $newPassword): void
    {
        Validator::make(
            ['password' => $newPassword],
            ['password' => PasswordRules::default()],
        )->validate();
    }
}
