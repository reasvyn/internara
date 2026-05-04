<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Exceptions\AuthException;
use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
     * @throws AuthException when credentials are invalid or account is blocked
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

            throw AuthException::invalidCredentials();
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

            throw AuthException::accountSuspended();
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

            throw AuthException::accountArchived();
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

            throw AuthException::accountInactive();
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
