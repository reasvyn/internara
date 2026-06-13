<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ConfirmPasswordAction extends BaseAction
{
    public function execute(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            SmartLogger::info('password_confirmation_failed')
                ->event('password_confirmation_failed')
                ->module('Auth')
                ->about($user)
                ->withPiiMasking()
                ->activityOnly()
                ->save();

            throw new RuntimeException(
                __('auth.password_confirmation_failed') ??
                    'The provided password does not match your current password.',
            );
        }

        session(['auth.password_confirmed_at' => time()]);

        SmartLogger::info('password_confirmed')
            ->event('password_confirmed')
            ->module('Auth')
            ->about($user)
            ->withPiiMasking()
            ->activityOnly()
            ->save();
    }
}
