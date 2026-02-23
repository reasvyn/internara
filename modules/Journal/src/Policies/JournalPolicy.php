<?php

declare(strict_types=1);

namespace Modules\Journal\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Journal\Models\JournalEntry;
use Modules\User\Models\User;

class JournalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the journal entry.
     */
    public function view(User $user, JournalEntry $entry): bool
    {
        if (! $user->can('journal.view')) {
            return false;
        }

        // Student can view their own
        if ($user->id === $entry->student_id) {
            return true;
        }

        // Teacher or Mentor assigned to this registration can view
        $registration = $entry->registration;

        return $user->id === $registration->teacher_id || $user->id === $registration->mentor_id;
    }

    /**
     * Determine if the user can create journal entries.
     */
    public function create(User $user): bool
    {
        return $user->can('journal.create') && $user->hasRole('student');
    }

    /**
     * Determine if the user can update the journal entry.
     */
    public function update(User $user, JournalEntry $entry): bool
    {
        if (! $user->can('journal.update')) {
            return false;
        }

        // Only student can update their own, and only if not approved
        return $user->id === $entry->student_id && $entry->latestStatus()?->name !== 'approved';
    }

    /**
     * Determine if the user can approve/reject the journal entry.
     */
    public function validate(User $user, JournalEntry $entry): bool
    {
        if (! $user->can('journal.validate')) {
            return false;
        }

        $registration = $entry->registration;

        // Either assigned Teacher OR assigned Mentor can validate
        return $user->id === $registration->teacher_id || $user->id === $registration->mentor_id;
    }

    /**
     * Determine if the user can delete the journal entry.
     */
    public function delete(User $user, JournalEntry $entry): bool
    {
        if (! $user->can('journal.delete')) {
            return false;
        }

        // Only student can delete their own draft.
        // Cannot delete once submitted or approved.
        return $user->id === $entry->student_id && $entry->latestStatus()?->name === 'draft';
    }
}
