<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * S1 - Secure: Implements strict authentication logic and auditing.
 * S3 - Scalable: Stateless action.
 */
class LoginAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(string $identifier, string $password, bool $remember = false): Authenticatable
    {
        $loginField = Str::contains($identifier, '@') ? 'email' : 'username';

        $credentials = [
            $loginField => $identifier,
            'password' => $password,
        ];

        if (!Auth::attempt($credentials, $remember)) {
            $this->logAuditAction->execute(
                action: 'login_failed',
                subjectType: User::class,
                payload: ['identifier' => $identifier],
                module: 'Auth'
            );

            abort(Response::HTTP_UNAUTHORIZED, __('auth.failed'));
        }

        /** @var User $user */
        $user = Auth::user();

        // Check account status
        if ($user->isSuspended() || $user->isArchived() || $user->isInactive()) {
            Auth::logout();
            
            $this->logAuditAction->execute(
                action: 'login_blocked',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['status' => $user->latestStatus()?->name],
                module: 'Auth'
            );

            abort(Response::HTTP_FORBIDDEN, __('auth.blocked'));
        }

        $this->logAuditAction->execute(
            action: 'login_success',
            subjectType: User::class,
            subjectId: $user->id,
            module: 'Auth'
        );

        return $user;
    }
}
