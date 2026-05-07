<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * S1 - Secure: Implements strict authentication logic and auditing.
 * S3 - Scalable: Stateless action.
 */
class LoginAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Attempt to authenticate a user.
     *
     * @throws RuntimeException when credentials are invalid or account is blocked
     */
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
            $this->logAuditAction->execute(
                action: 'login_failed',
                subjectType: User::class,
                payload: ['identifier' => $identifier],
                module: 'Auth',
            );

            throw new RuntimeException(__('auth.failed'));
        }

        /** @var User $user */
        $user = Auth::user();

        // Check account status
        if ($user->isSuspended()) {
            Auth::logout();

            $this->logAuditAction->execute(
                action: 'login_blocked',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['status' => 'suspended'],
                module: 'Auth',
            );

            throw new RuntimeException(__('auth.blocked'));
        }

        if ($user->isArchived()) {
            Auth::logout();

            $this->logAuditAction->execute(
                action: 'login_blocked',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['status' => 'archived'],
                module: 'Auth',
            );

            throw new RuntimeException(__('auth.blocked'));
        }

        if ($user->isInactive()) {
            Auth::logout();

            $this->logAuditAction->execute(
                action: 'login_blocked',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['status' => 'inactive'],
                module: 'Auth',
            );

            throw new RuntimeException(__('auth.blocked'));
        }

        $this->logAuditAction->execute(
            action: 'login_success',
            subjectType: User::class,
            subjectId: $user->id,
            module: 'Auth',
        );

        return $user;
    }
}
