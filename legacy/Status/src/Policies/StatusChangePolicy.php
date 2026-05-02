<?php

declare(strict_types=1);

namespace Modules\Status\Policies;

use Modules\User\Models\User;

class StatusChangePolicy
{
    /**
     * Determine if user can view account status information.
     */
    public function view(User $user, User $target): bool
    {
        // Users can view their own status
        if ($user->id === $target->id) {
            return true;
        }

        // Admins can view any user's status
        if ($this->isAdmin($user)) {
            return true;
        }

        // Teachers/Supervisors can view their student's status
        if ($this->canSupervise($user, $target)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can transition target's account status.
     */
    public function transition(User $user, User $target): bool
    {
        // Users cannot transition others
        if ($user->id !== $target->id) {
            // Only super admins can transition other users
            return $user->role === 'super_admin';
        }

        // Users can only transition themselves in limited ways
        // (actual transition validation happens in StatusTransitionService)
        return true;
    }

    /**
     * Determine if user can verify another user's account.
     */
    public function verify(User $user, User $target): bool
    {
        // Super admins can verify anyone
        if ($user->role === 'super_admin') {
            return true;
        }

        // Regular admins can verify non-admin users
        if ($user->role === 'admin' && ! $this->isAdmin($target)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can restrict another user's account.
     */
    public function restrict(User $user, User $target): bool
    {
        // Super admins can restrict anyone (except other super admins)
        if ($user->role === 'super_admin') {
            return $target->role !== 'super_admin';
        }

        // Admins can restrict non-admin users
        if ($user->role === 'admin' && ! $this->isAdmin($target)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can suspend another user's account.
     */
    public function suspend(User $user, User $target): bool
    {
        // Super admins can suspend anyone (except other super admins)
        if ($user->role === 'super_admin') {
            return $target->role !== 'super_admin';
        }

        // Admins can suspend non-admin users
        if ($user->role === 'admin' && ! $this->isAdmin($target)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can archive another user's account.
     */
    public function archive(User $user, User $target): bool
    {
        // Only super admins can archive
        if ($user->role === 'super_admin') {
            return $target->role !== 'super_admin';
        }

        return false;
    }

    /**
     * Determine if user can view account status history/audit trail.
     */
    public function viewHistory(User $user, User $target): bool
    {
        // Users can view their own history
        if ($user->id === $target->id) {
            return true;
        }

        // Only admins can view other users' history
        return $this->isAdmin($user);
    }

    /**
     * Determine if user can view account restrictions.
     */
    public function viewRestrictions(User $user, User $target): bool
    {
        // Users can view their own restrictions
        if ($user->id === $target->id) {
            return true;
        }

        // Only admins can view other users' restrictions
        return $this->isAdmin($user);
    }

    /**
     * Determine if user can manage (add/remove) account restrictions.
     */
    public function manageRestrictions(User $user, User $target): bool
    {
        // Super admins can manage anyone's restrictions (except other super admins)
        if ($user->role === 'super_admin') {
            return $target->role !== 'super_admin';
        }

        // Admins can manage non-admin users' restrictions
        if ($user->role === 'admin' && ! $this->isAdmin($target)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can manually unlock a locked account.
     */
    public function unlock(User $user, User $target): bool
    {
        // Super admins can unlock anyone (except other super admins)
        if ($user->role === 'super_admin') {
            return $target->role !== 'super_admin';
        }

        // Admins can unlock non-admin users
        if ($user->role === 'admin' && ! $this->isAdmin($target)) {
            return true;
        }

        return false;
    }

    /**
     * Helper: Check if user has admin privileges.
     */
    private function isAdmin(User $user): bool
    {
        return \in_array($user->role, ['super_admin', 'admin'], true);
    }

    /**
     * Helper: Check if user supervises target.
     */
    private function canSupervise(User $user, User $target): bool
    {
        // Teachers can view their students' status
        if ($user->role === 'teacher') {
            // Check if user teaches any class that target is in
            // (Implementation depends on your class/enrollment structure)
            return $user
                ->classes()
                ->whereHas('students', fn ($q) => $q->where('student_id', $target->id))
                ->exists();
        }

        // Supervisors can view their internship students
        if ($user->role === 'supervisor') {
            return $user->internships()->where('student_id', $target->id)->exists();
        }

        return false;
    }
}
