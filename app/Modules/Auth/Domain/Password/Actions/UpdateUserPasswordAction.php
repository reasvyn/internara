<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Actions;

use App\Modules\Auth\Domain\Password\Events\PasswordUpdated;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Support\PasswordRules;
use App\Modules\User\Models\User;
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
