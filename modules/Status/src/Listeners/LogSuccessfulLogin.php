<?php

declare(strict_types=1);

namespace Modules\Status\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Status\Services\AccountAuditLogger;
use Modules\Status\Services\SessionExpirationService;
use Modules\User\Models\User;

/**
 * Handles the Illuminate\Auth\Events\Login event.
 *
 * [S3 - Scalable] Decoupled from Auth module to handle Status/Audit specific logic.
 */
class LogSuccessfulLogin
{
    public function __construct(
        protected AccountAuditLogger $auditLogger,
        protected SessionExpirationService $sessionExpiration,
    ) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $ipAddress = request()->ip();

            $this->auditLogger->logSuccessfulLogin(user: $user, ipAddress: $ipAddress);

            // Initialize session expiration tracking for admin roles
            if (\in_array($user->role, ['super_admin', 'admin'], true)) {
                $sessionId = request()->getSession()->getId();
                $this->sessionExpiration->recordSessionStart(
                    user: $user,
                    sessionId: $sessionId,
                    ipAddress: $ipAddress,
                );
            }
        }
    }
}
