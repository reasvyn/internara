<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use Illuminate\Support\Facades\Password;

/**
 * S1 - Secure: Implements secure password reset link sending.
 * S3 - Scalable: Stateless action.
 */
class SendPasswordResetLinkAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    public function execute(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        $this->logAuditAction->execute(
            action: 'password_reset_link_requested',
            payload: ['email' => $email, 'status' => $status],
            module: 'Auth'
        );

        return $status;
    }
}
