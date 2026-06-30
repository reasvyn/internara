<?php

declare(strict_types=1);

namespace App\Core\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Provides common role-based authorization checks for module policies.
 *
 * Reduces duplication of hasAnyRole(['super_admin', 'admin']) patterns
 * across all policy classes.
 *
 * @deprecated Use MentorEntity proxy methods (mentorProxyFor()->canXxx())
 *             for role checks that involve mentor/student relationships.
 *             isAdmin() and hasAnyOfRoles() are exceptions — they remain
 *             for admin-only gates.
 */
trait AuthorizesRoles
{
    /**
     * Check if user has admin or super_admin role.
     *
     * This method is NOT deprecated — it gates admin-specific features
     * (settings, backups, Pulse) that are not subject to proxy.
     */
    protected function isAdmin(Model $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /** @deprecated Use mentorProxyFor()->canGradeSubmission() or canVerifyAttendance() */
    protected function isTeacher(Model $user): bool
    {
        return $user->hasRole('teacher');
    }

    /** @deprecated Use MentorEntity::isMentor() */
    protected function isStudent(Model $user): bool
    {
        return $user->hasRole('student');
    }

    /** @deprecated Use mentorProxyFor()->canVerifyLogbook() or canReviewSupervisionLog() */
    protected function isSupervisor(Model $user): bool
    {
        return $user->hasRole('supervisor');
    }

    /** @deprecated Use MentorEntity proxy checks for teacher role */
    protected function isAdminOrTeacher(Model $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    /**
     * Check if user can manage any role (admin gate).
     *
     * This method is NOT deprecated.
     */
    protected function canManageAnyRole(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Generic role check.
     *
     * This method is NOT deprecated — use for broad role gating.
     */
    protected function hasAnyOfRoles(Model $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }
}
