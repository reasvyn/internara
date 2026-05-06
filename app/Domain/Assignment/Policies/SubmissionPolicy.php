<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Policies;

use App\Domain\Assignment\Models\Submission;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view/submit their own submissions.
 */
class SubmissionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $assignment = $submission->assignment;

        if ($this->isTeacher($user) && $assignment && $assignment->created_by === $user->id) {
            return true;
        }

        return $submission->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $submission->student_id === $user->id && $submission->status?->value === 'submitted';
    }

    public function verify(User $user, Submission $submission): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->isAdmin($user);
    }
}
