<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction extends BaseCommandAction
{
    public function execute(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        SmartLogger::info('password_reset_link_requested')
            ->event('password_reset_link_requested')
            ->module('Auth')
            ->withPayload(['email' => $email, 'status' => $status])
            ->withPiiMasking()
            ->activityOnly()
            ->save();

        return $status;
    }
}
