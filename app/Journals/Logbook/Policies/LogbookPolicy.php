<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Policies;

use App\Core\Policies\BasePolicy;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;

/**
 * S1 - Secure: Students can only view/edit their own journals. Submitted journals are immutable.
 * L1 - Optional supervisor note: supervisors can add notes without affecting entry status.
 */
class LogbookPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function view(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (
            $this->isTeacher($user) &&
            $entry->registration &&
            $entry->registration
                ->mentors()
                ->where('user_id', $user->id)
                ->where('internship_group_members.role', 'teacher')
                ->exists()
        ) {
            return true;
        }

        if (
            $this->isSupervisor($user) &&
            $entry->registration &&
            $entry->registration
                ->mentors()
                ->where('user_id', $user->id)
                ->where('internship_group_members.role', 'supervisor')
                ->exists()
        ) {
            return true;
        }

        return $entry->user_id === $user->id;
    }

    public function addSupervisorNote(User $user, Logbook $entry): bool
    {
        if (! $this->isSupervisor($user)) {
            return false;
        }

        return $entry->registration &&
            $entry->registration
                ->mentors()
                ->where('user_id', $user->id)
                ->where('internship_group_members.role', 'supervisor')
                ->exists();
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== LogbookStatus::SUBMITTED;
    }

    public function delete(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== LogbookStatus::SUBMITTED;
    }
}
