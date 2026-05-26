<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\PasswordRules;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class UpdateUserPasswordAction extends BaseAction
{
    public function execute(User $user, string $newPassword): void
    {
        $this->validateNewPassword($newPassword);

        try {
            DB::transaction(function () use ($user, $newPassword) {
                $user->update([
                    'password' => Hash::make($newPassword),
                ]);

                SmartLogger::info('password_updated_manually')
                    ->event('password_updated_manually')
                    ->module('Auth')
                    ->about($user)
                    ->activityOnly()
                    ->save();
            });
        } catch (\Throwable $e) {
            SmartLogger::error('Failed to update user password')
                ->withPayload([
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ])
                ->systemOnly()
                ->save();

            throw new RuntimeException('Failed to update password.', 0, $e);
        }
    }

    protected function validateNewPassword(string $newPassword): void
    {
        Validator::make([
            'password' => $newPassword,
        ], [
            'password' => PasswordRules::default(),
        ])->validate();
    }
}
