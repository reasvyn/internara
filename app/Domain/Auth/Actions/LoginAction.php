<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class LoginAction extends BaseAction
{
    public function execute(
        string $identifier,
        string $password,
        bool $remember = false,
    ): Authenticatable {
        $loginField = Str::contains($identifier, '@') ? 'email' : 'username';

        $credentials = [
            $loginField => $identifier,
            'password' => $password,
        ];

        if (! Auth::attempt($credentials, $remember)) {
            SmartLogger::info('login_failed')
                ->event('login_failed')
                ->module('Auth')
                ->withPayload(['identifier' => $identifier])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.failed'));
        }

        /** @var User $user */
        $user = Auth::user();

        $apprentice = $user->asApprentice();

        if ($apprentice->isSuspended()) {
            Auth::logout();

            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['status' => 'suspended'])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }

        if ($apprentice->isArchived()) {
            Auth::logout();

            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['status' => 'archived'])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }

        if ($apprentice->isInactive()) {
            Auth::logout();

            SmartLogger::info('login_blocked')
                ->event('login_blocked')
                ->module('Auth')
                ->about($user)
                ->withPayload(['status' => 'inactive'])
                ->activityOnly()
                ->save();

            throw new RuntimeException(__('auth.blocked'));
        }

        SmartLogger::info('login_success')
            ->event('login_success')
            ->module('Auth')
            ->about($user)
            ->activityOnly()
            ->save();

        return $user;
    }
}
