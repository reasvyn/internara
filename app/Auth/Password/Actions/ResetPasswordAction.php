<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;
use App\Core\Services\SmartLogger;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class ResetPasswordAction extends BaseCommandAction
{
    public function execute(
        string $email,
        string $token,
        string $password,
        string $passwordConfirmation,
    ): ActionResponse {
        $throttleKey = 'reset-password:' . Str::lower($email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->log('password_reset_throttled', null, [
                'email' => $email,
                'seconds' => $seconds,
            ]);

            throw new RejectedException(__('auth.throttle', ['seconds' => $seconds]));
        }

        RateLimiter::hit($throttleKey, 300);

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

        return ActionResponse::ok();
    }
}
