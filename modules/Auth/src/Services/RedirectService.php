<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Services\Contracts\RedirectService as Contract;
use Modules\Permission\Enums\Role;
use Modules\Shared\Services\BaseService;

/**
 * Class RedirectService
 *
 * Handles post-authentication redirection logic based on user roles and status.
 */
final class RedirectService extends BaseService implements Contract
{
    /**
     * Get the target URL for the authenticated user.
     */
    public function getTargetUrl(Authenticatable $user): string
    {
        // Users without an email address skip the verification gate entirely.
        // They receive a soft dashboard notification instead.
        if ($user->email && ! $user->hasVerifiedEmail() && setting('require_email_verification', true)) {
            return route('verification.notice');
        }

        return $this->getTargetUrlSkipVerification($user);
    }

    /**
     * Get the target URL bypassing the email verification gate.
     * Used when the user explicitly skips email verification.
     */
    public function getTargetUrlSkipVerification(Authenticatable $user): string
    {
        if ($user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])) {
            return route('admin.dashboard');
        }

        if ($user->hasRole(Role::TEACHER->value)) {
            return route('teacher.dashboard');
        }

        if ($user->hasRole(Role::MENTOR->value)) {
            return route('mentor.dashboard');
        }

        return route('student.dashboard');
    }
}
