<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Services\Contracts\RedirectService as Contract;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class RedirectService
 *
 * Handles logic for determining the destination URL after login/registration.
 */
class RedirectService implements Contract
{
    /**
     * Get the target URL for a user based on their roles and status.
     */
    public function getTargetUrl(User $user): string
    {
        // If the user is not an admin and hasn't verified their email,
        // redirect them to the verification notice page.
        if (
            ! $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value]) &&
            ! $user->hasVerifiedEmail()
        ) {
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

        if ($user->hasRole(Role::STUDENT->value)) {
            return route('student.dashboard');
        }

        return route('student.dashboard');
    }
}
