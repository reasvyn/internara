<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction extends BaseCommandAction
{
    public function execute(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        $this->log('password_reset_link_requested', null, [
            'email' => $email,
            'status' => $status,
        ]);

        return $status;
    }
}
