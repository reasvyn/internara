<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class ResetPasswordAction extends BaseAction
{
    public function execute(
        string $email,
        string $token,
        string $password,
        string $passwordConfirmation,
    ): bool {
        $credentials = [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ];

        $status = Password::reset($credentials, function (User $user, string $password) {
            DB::transaction(function () use ($user, $password) {
                $user->fill([
                    'password' => Hash::make($password),
                ])->save();

                SmartLogger::info('password_reset_success')
                    ->event('password_reset_success')
                    ->module('Auth')
                    ->about($user)
                    ->activityOnly()
                    ->save();
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
            SmartLogger::info('password_reset_failed')
                ->event('password_reset_failed')
                ->module('Auth')
                ->withPayload(['email' => $email, 'status' => $status])
                ->activityOnly()
                ->save();

            $message = match ($status) {
                Password::INVALID_TOKEN => __('passwords.token'),
                Password::INVALID_USER => __('passwords.user'),
                default => __('passwords.token'),
            };

            throw new RuntimeException($message);
        }

        return true;
    }
}
