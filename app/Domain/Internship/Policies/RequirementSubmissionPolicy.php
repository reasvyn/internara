<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Internship\Models\RequirementSubmission;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only access their own submissions. File access restricted.
 */
class RequirementSubmissionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
        ]);
    }

    public function view(User $user, RequirementSubmission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $registration = $submission->registration;

        if ($this->isTeacher($user) && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($this->isSupervisor($user) && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, RequirementSubmission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $submission->registration->student_id === $user->id &&
            $submission->latestStatus()?->name === 'pending';
    }

    public function verify(User $user, RequirementSubmission $submission): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function delete(User $user, RequirementSubmission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $submission->registration->student_id === $user->id &&
            $submission->latestStatus()?->name === 'pending';
    }
}
