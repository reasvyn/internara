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
        if (! $user->hasVerifiedEmail() && setting('require_email_verification', true)) {
            return route('verification.notice');
        }

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
