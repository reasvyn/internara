<?php

declare(strict_types=1);

namespace Modules\Status\Listeners;

use Illuminate\Auth\Events\Failed;
use Modules\Status\Services\AccountAuditLogger;

/**
 * Handles the Illuminate\Auth\Events\Failed event.
 * 
 * [S3 - Scalable] Decoupled from Auth module to handle Status/Audit specific logic.
 */
class LogFailedLogin
{
    public function __construct(
        protected AccountAuditLogger $auditLogger,
    ) {}

    public function handle(Failed $event): void
    {
        $user = $event->user;

        if ($user instanceof \Modules\User\Models\User) {
            $this->auditLogger->logFailedLogin(
                user: $user,
                ipAddress: request()->ip(),
            );
        }
    }
}
