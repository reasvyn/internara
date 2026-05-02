<?php

declare(strict_types=1);

namespace Modules\Assignment\Policies;

use Modules\Assignment\Models\Submission;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class SubmissionPolicy
 *
 * Policy for Submission model operations.
 */
class SubmissionPolicy
{
    /**
     * Determine whether the user can view any submissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSIGNMENT_VIEW->value);
    }

    /**
     * Determine whether the user can view the submission.
     */
    public function view(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo(Permission::ASSIGNMENT_VIEW->value)) {
            return false;
        }

        if ($user->id === $submission->student_id) {
            return true;
        }

        return $user->hasAnyRole([
            Role::ADMIN->value,
            Role::SUPER_ADMIN->value,
            Role::TEACHER->value,
            Role::MENTOR->value,
        ]);
    }

    /**
     * Determine whether the user can create submissions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::STUDENT->value);
    }

    /**
     * Determine whether the user can update submissions.
     */
    public function update(User $user, Submission $submission): bool
    {
        return $user->id === $submission->student_id && ! $submission->isVerified();
    }

    /**
     * Determine whether the user can validate submissions.
     */
    public function validate(User $user, Submission $submission): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN->value,
            Role::SUPER_ADMIN->value,
            Role::TEACHER->value,
            Role::MENTOR->value,
        ]);
    }

    /**
     * Determine whether the user can delete submissions.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can force delete submissions.
     */
    public function forceDelete(User $user, Submission $submission): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
