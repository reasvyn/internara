<?php

declare(strict_types=1);

namespace Modules\Journal\Policies;

use Modules\Journal\Models\JournalEntry;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class JournalPolicy
 *
 * Policy for JournalEntry model operations.
 */
class JournalPolicy
{
    /**
     * Determine whether the user can view any journal entries.
     */
    public function viewAny(?User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasAnyPermission([
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_MANAGE->value,
        ]) ?? false;
    }

    /**
     * Determine whether the user can view the journal entry.
     */
    public function view(User $user, JournalEntry $entry): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        if ($user->id === $entry->student_id) {
            return true;
        }

        return $user->hasAnyPermission([
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create journal entries.
     */
    public function create(User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasAnyPermission([
            Permission::JOURNAL_CREATE->value,
            Permission::JOURNAL_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can update the journal entry.
     */
    public function update(User $user, JournalEntry $entry): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        if ($user->id === $entry->student_id) {
            return $user->hasPermissionTo(Permission::JOURNAL_UPDATE->value);
        }

        return $user->hasAnyPermission([
            Permission::JOURNAL_UPDATE->value,
            Permission::JOURNAL_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can validate the journal entry.
     */
    public function validate(User $user, JournalEntry $entry): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user->hasAnyPermission([
            Permission::JOURNAL_APPROVE->value,
            Permission::JOURNAL_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can delete the journal entry.
     */
    public function delete(User $user, JournalEntry $entry): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        if ($user->id === $entry->student_id) {
            return false;
        }

        return $user->hasAnyPermission([
            Permission::JOURNAL_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can force delete the journal entry.
     */
    public function forceDelete(User $user, JournalEntry $entry): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
