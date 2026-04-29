<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * S1 - Secure: Implements secure logout and auditing.
 * S3 - Scalable: Stateless action.
 */
class LogoutAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(): void
    {
        $user = Auth::user();

        if ($user) {
            $this->logAuditAction->execute(
                action: 'logout',
                subjectType: User::class,
                subjectId: $user->getAuthIdentifier(),
                module: 'Auth'
            );
        }

        Auth::logout();
    }
}
