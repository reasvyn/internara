<?php

declare(strict_types=1);

namespace App\Policies\Logbook;

use App\Models\Logbook;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

/**
 * S1 - Secure: Students can only view/edit their own journals. Submitted journals are immutable.
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
            $entry->registration->teacher_id === $user->id
        ) {
            return true;
        }

        if (
            $this->isSupervisor($user) &&
            $entry->registration &&
            $entry->registration->mentor_id === $user->id
        ) {
            return true;
        }

        return $entry->user_id === $user->id;
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

        return $entry->user_id === $user->id && $entry->status !== 'submitted';
    }

    public function delete(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== 'submitted';
    }
}
