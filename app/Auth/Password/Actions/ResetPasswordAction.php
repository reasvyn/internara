<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Auth\Password\Data\ResetPasswordData;
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
    public function execute(ResetPasswordData $data): ActionResponse
    {
        $throttleKey = 'reset-password:'.Str::lower($data->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->log('password_reset_throttled', null, [
                'email' => $data->email,
                'seconds' => $seconds,
            ]);

            throw new RejectedException(__('auth.throttle', ['seconds' => $seconds]));
        }

        RateLimiter::hit($throttleKey, 300);

        if ($data->password !== $data->passwordConfirmation) {
            SmartLogger::info('password_reset_confirmation_mismatch')
                ->event('password_reset_confirmation_mismatch')
                ->module('Auth')
                ->withPayload(['email' => $data->email])
                ->withPiiMasking()
                ->activityOnly()
                ->save();

            throw new RejectedException(__('validation.custom.password.confirmed'));
        }

        $credentials = [
            'email' => $data->email,
            'token' => $data->token,
            'password' => $data->password,
            'password_confirmation' => $data->passwordConfirmation,
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
