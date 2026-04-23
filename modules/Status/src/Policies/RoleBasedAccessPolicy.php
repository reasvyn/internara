<?php

declare(strict_types=1);

namespace Modules\Status\Policies;

use Modules\User\Models\User;
use Modules\Status\Enums\AccountStatus;

/**
 * RoleBasedAccessPolicy
 *
 * Fine-grained permission gates for account status operations
 * based on user roles. Follows principle of least privilege.
 *
 * Roles:
 * - Super Admin (super_admin): Full system access, immutable
 * - Admin (admin): Can manage non-admin users
 * - Supervisor (supervisor): Can manage student accounts only
 * - Teacher (teacher): Limited read-only access
 * - Student (student): Self-service only
 */
class RoleBasedAccessPolicy
{
    /**
     * Determine if user can view another user's account
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function view(User $user, User $target): bool
    {
        // Can always view self
        if ($user->id === $target->id) {
            return true;
        }

        // Super Admin can view all
        if ($user->isSuper()) {
            return true;
        }

        // Admin can view non-admins
        if ($user->isAdmin() && !$target->isAdmin() && !$target->isSuper()) {
            return true;
        }

        // Supervisor can view students only
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return true;
        }

        // Teacher can view students assigned to their classes
        if ($user->isTeacher() && $target->hasRole('student')) {
            return $this->isTeacherOf($user, $target);
        }

        return false;
    }

    /**
     * Determine if user can change another user's status
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @param AccountStatus $newStatus Proposed status
     * @return bool
     */
    public function changeStatus(User $user, User $target, AccountStatus $newStatus): bool
    {
        // Cannot change own status (except self-service transitions)
        if ($user->id === $target->id) {
            return $this->allowedSelfTransitions($user)->contains($newStatus);
        }

        // Super Admin can change any status (except protecting other Super Admins)
        if ($user->isSuper()) {
            return $newStatus !== AccountStatus::PROTECTED || !$target->isSuper();
        }

        // Admin can:
        // - Verify non-admin users (ACTIVATED → VERIFIED)
        // - Restrict non-admin users
        // - Suspend non-admin users
        if ($user->isAdmin()) {
            if ($target->isAdmin() || $target->isSuper()) {
                return false; // Cannot change other admins
            }

            return in_array($newStatus, [
                AccountStatus::VERIFIED,
                AccountStatus::RESTRICTED,
                AccountStatus::SUSPENDED,
            ]);
        }

        // Supervisor can restrict/suspend students only
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return in_array($newStatus, [
                AccountStatus::RESTRICTED,
                AccountStatus::SUSPENDED,
            ]);
        }

        // Teachers cannot change status
        if ($user->isTeacher()) {
            return false;
        }

        // Students cannot change status
        return false;
    }

    /**
     * Determine if user can verify another user's account
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function verify(User $user, User $target): bool
    {
        // Super Admin can verify anyone except other Super Admins
        if ($user->isSuper()) {
            return !$target->isSuper();
        }

        // Admin can verify non-admin users
        if ($user->isAdmin()) {
            return !$target->isAdmin() && !$target->isSuper();
        }

        // Supervisor can verify students
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can restrict another user's access
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function restrict(User $user, User $target): bool
    {
        // Cannot restrict same or higher role
        if ($user->id === $target->id) {
            return false;
        }

        // Super Admin can restrict anyone except other Super Admins
        if ($user->isSuper()) {
            return !$target->isSuper();
        }

        // Admin can restrict non-admin users only
        if ($user->isAdmin()) {
            return !$target->isAdmin() && !$target->isSuper();
        }

        // Supervisor can restrict students
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can suspend another user
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function suspend(User $user, User $target): bool
    {
        // Cannot suspend self
        if ($user->id === $target->id) {
            return false;
        }

        // Super Admin can suspend anyone except other Super Admins
        if ($user->isSuper()) {
            return !$target->isSuper();
        }

        // Admin can suspend non-admin users only
        if ($user->isAdmin()) {
            return !$target->isAdmin() && !$target->isSuper();
        }

        // Supervisor can suspend students only
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can unlock a suspended account
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function unlock(User $user, User $target): bool
    {
        // Same rules as suspend - who can suspend can unlock
        return $this->suspend($user, $target);
    }

    /**
     * Determine if user can archive another user's account
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function archive(User $user, User $target): bool
    {
        // Cannot archive self
        if ($user->id === $target->id) {
            return false;
        }

        // Only Super Admin and Admin can archive
        return $user->isSuper() || $user->isAdmin();
    }

    /**
     * Determine if user can export another user's data (GDPR)
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function exportData(User $user, User $target): bool
    {
        // Can export own data
        if ($user->id === $target->id) {
            return true;
        }

        // Super Admin and Admin can export any data
        if ($user->isSuper() || $user->isAdmin()) {
            return true;
        }

        // Supervisor can export student data only
        if ($user->isSupervisor() && $target->hasRole('student')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can delete another user's account
     *
     * @param User $user Authenticated user
     * @param User $target Target user
     * @return bool
     */
    public function delete(User $user, User $target): bool
    {
        // Cannot delete self
        if ($user->id === $target->id) {
            return false;
        }

        // Cannot delete Super Admin accounts (only anonymize)
        if ($target->isSuper()) {
            return false;
        }

        // Only Super Admin can delete users
        return $user->isSuper();
    }

    /**
     * Get allowed self-service transitions for user
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    private function allowedSelfTransitions(User $user): \Illuminate\Support\Collection
    {
        return collect([
            // Students can only self-activate
            AccountStatus::ACTIVATED,
        ]);
    }

    /**
     * Check if teacher has assigned student
     *
     * @param User $teacher
     * @param User $student
     * @return bool
     */
    private function isTeacherOf(User $teacher, User $student): bool
    {
        // Check if student is in any of teacher's classes
        // This would depend on your actual relationship structure
        return $teacher->classes()
            ->whereHas('students', function ($query) use ($student) {
                $query->where('id', $student->id);
            })
            ->exists();
    }
}
