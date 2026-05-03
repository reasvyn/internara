<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Logbook\Models\LogbookEntry;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view/edit their own journals. Submitted journals are immutable.
 */
class LogbookEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function view(User $user, LogbookEntry $entry): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (
            $user->hasRole('teacher') &&
            $entry->registration &&
            $entry->registration->teacher_id === $user->id
        ) {
            return true;
        }

        if (
            $user->hasRole('supervisor') &&
            $entry->registration &&
            $entry->registration->mentor_id === $user->id
        ) {
            return true;
        }

        return $entry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, LogbookEntry $entry): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== 'submitted';
    }

    public function delete(User $user, LogbookEntry $entry): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== 'submitted';
    }
}
