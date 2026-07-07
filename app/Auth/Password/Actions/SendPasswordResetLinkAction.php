<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class SendPasswordResetLinkAction extends BaseCommandAction
{
    public function execute(string $email): ActionResponse
    {
        $throttleKey = 'forgot-password:' . Str::lower($email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->log('password_reset_link_throttled', null, [
                'email' => $email,
                'seconds' => $seconds,
            ]);

            return ActionResponse::ok(Password::RESET_LINK_SENT);
        }

        RateLimiter::hit($throttleKey, 3600);

        $status = Password::sendResetLink(['email' => $email]);

        $this->log('password_reset_link_requested', null, [
            'email' => $email,
            'status' => $status,
        ]);

        return ActionResponse::ok($status);
    }
}
