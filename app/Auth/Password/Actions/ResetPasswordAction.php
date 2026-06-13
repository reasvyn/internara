<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction extends BaseAction
{
    public function execute(
        string $email,
        string $token,
        string $password,
        string $passwordConfirmation,
    ): bool {
        if ($password !== $passwordConfirmation) {
            SmartLogger::info('password_reset_confirmation_mismatch')
                ->event('password_reset_confirmation_mismatch')
                ->module('Auth')
                ->withPayload(['email' => $email])
                ->withPiiMasking()
                ->activityOnly()
                ->save();

            throw new RejectedException(__('validation.custom.password.confirmed'));
        }

        $credentials = [
            'email' => $email,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ];

        $status = Password::reset($credentials, function (User $user, string $password) {
            DB::transaction(function () use ($user, $password) {
                $user
                    ->fill([
                        'password' => Hash::make($password),
                    ])
                    ->save();

                SmartLogger::info('password_reset_success')
                    ->event('password_reset_success')
                    ->module('Auth')
                    ->about($user)
                    ->withPiiMasking()
                    ->activityOnly()
                    ->save();
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
            SmartLogger::info('password_reset_failed')
                ->event('password_reset_failed')
                ->module('Auth')
                ->withPayload(['email' => $email, 'status' => $status])
                ->withPiiMasking()
                ->activityOnly()
                ->save();

            $message = match ($status) {
                Password::INVALID_TOKEN => __('passwords.token'),
                Password::INVALID_USER => __('passwords.user'),
                default => __('passwords.token'),
            };

            throw new RejectedException($message);
        }

        return true;
    }
}
