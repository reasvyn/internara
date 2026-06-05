<?php

declare(strict_types=1);

namespace App\User\Password\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction extends BaseAction
{
    public function execute(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        SmartLogger::info('password_reset_link_requested')
            ->event('password_reset_link_requested')
            ->module('Auth')
            ->withPayload(['email' => $email, 'status' => $status])
            ->activityOnly()
            ->save();

        return $status;
    }
}
