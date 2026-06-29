<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction extends BaseCommandAction
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
            $this->transaction(function () use ($user, $password) {
                $user
                    ->fill([
                        'password' => Hash::make($password),
                    ])
                    ->save();

                $this->log('password_reset_success', $user);
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
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
