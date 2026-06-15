<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Auth\Password\Events\PasswordUpdated;
use App\Core\Actions\BaseCommandAction;
use App\Core\Support\PasswordRules;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UpdateUserPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $newPassword): void
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
    }

    protected function validateNewPassword(string $newPassword): void
    {
        Validator::make(
            ['password' => $newPassword],
            ['password' => PasswordRules::default()],
        )->validate();
    }
}
