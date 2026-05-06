<?php

declare(strict_types=1);

namespace App\Domain\Shared\Policies\Concerns;

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;

/**
 * Provides common role-based authorization checks for domain policies.
 *
 * Reduces duplication of hasAnyRole(['super_admin', 'admin']) patterns
 * across all policy classes.
 */
trait AuthorizesRoles
{
    protected function isAdmin(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::ADMIN->value,
        ]);
    }

    protected function isTeacher(User $user): bool
    {
        return $user->hasRole(Role::TEACHER->value);
    }

    protected function isStudent(User $user): bool
    {
        return $user->hasRole(Role::STUDENT->value);
    }

    protected function isSupervisor(User $user): bool
    {
        return $user->hasRole(Role::SUPERVISOR->value);
    }

    protected function isAdminOrTeacher(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN->value,
            Role::ADMIN->value,
            Role::TEACHER->value,
        ]);
    }

    protected function canManageAnyRole(User $user): bool
    {
        return $this->isAdmin($user);
    }

    protected function hasAnyOfRoles(User $user, array $roles): bool
    {
        $roleValues = array_map(
            static fn ($role) => $role instanceof Role ? $role->value : $role,
            $roles,
        );

        return $user->hasAnyRole($roleValues);
    }
}
