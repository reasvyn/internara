<?php

declare(strict_types=1);

namespace App\Core\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Provides common role-based authorization checks for module policies.
 *
 * Reduces duplication of hasAnyRole(['super_admin', 'admin']) patterns
 * across all policy classes.
 */
trait AuthorizesRoles
{
    protected function isAdmin(Model $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    protected function isTeacher(Model $user): bool
    {
        return $user->hasRole('teacher');
    }

    protected function isStudent(Model $user): bool
    {
        return $user->hasRole('student');
    }

    protected function isSupervisor(Model $user): bool
    {
        return $user->hasRole('supervisor');
    }

    protected function isAdminOrTeacher(Model $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    protected function canManageAnyRole(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    protected function hasAnyOfRoles(Model $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }
}
